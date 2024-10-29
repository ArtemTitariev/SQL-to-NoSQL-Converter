<?php

namespace App\Http\Controllers;

use App\Enums\MongoManyToManyRelation;
use App\Enums\MongoRelationType;
use App\Enums\RelationType;
use App\Models\Convert;
use App\Models\IdMapping;
use App\Models\MongoSchema\Collection;
use App\Models\MongoSchema\Field;
use App\Models\MongoSchema\LinkEmbedd;
use App\Models\MongoSchema\ManyToManyLink;
use App\Models\SQLSchema\ForeignKey;
use App\Models\SQLSchema\Table;

use App\Services\DatabaseConnections\ConnectionCreator;
use App\Services\DataTypes\Converter;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TestEtlController extends Controller
{
    public $maxEmbeddingDepth = 5;

    public function test(Request $request)
    {

        DB::table('id_mappings')->truncate();
        // -------------------------------------------

        // $id = $request->input('id');
        $id = 1;
        $convert = Convert::find($id);

        $sqlDatabase = $convert->sqlDatabase;
        $mongoDatabase = $convert->mongoDatabase;

        // $collections = $mongoDatabase->collections()->with(['fields', 'linksEmbeddsFrom', 'manyToManyPivot'])
        //     ->whereDoesntHave('linksEmbeddsTo')
        //     ->whereDoesntHave('manyToManyFirst')
        //     ->whereDoesntHave('manyToManySecond')
        //     ->get();

        $sqlConnection = ConnectionCreator::create($sqlDatabase);

        $mongoConnection = ConnectionCreator::create($mongoDatabase);

        $mongoConnection->dropCollection('posts'); //------------------------------------------------------------------------------

        $collections = $mongoDatabase->collections()->with(['fields', 'linksEmbeddsFrom', 'manyToManyPivot'])
            ->whereHas('manyToManyPivot')
            ->whereDoesntHave('linksEmbeddsTo')
            ->whereDoesntHave('manyToManyFirst')
            ->whereDoesntHave('manyToManySecond')
            ->get();

        foreach ($collections as $collection) {
            // $fieldNames = $collection->fields()->pluck('name')->toArray();
            $table = $collection->sqlTable;
            // $linksEmbeddsFrom = $collection->linksEmbeddsFrom;

            $sqlConnection->table($table->name)
                ->orderBy($table->getOrderingColumnName())
                ->lazy()
                ->each(function (object $record) use (
                    $collection,
                    $sqlConnection,
                    $mongoConnection,
                ) {
                    $record = (array) $record;
                    // LINK WITH PIWOT

                    // 1. Process main (first ans second) collections

                    // -- get and transform fields
                    // -- process links

                    // -- process embedds (recursively)

                    // -- write to MongoDB

                    // 2. Process pivot
                    // -- the same stuff

                    // ------------------------

                    // 1. First
                    $rel = $collection->manyToManyPivot()->first();
                    $first = $rel->collection1()->with(['fields'])->first();

                    $start_time = microtime(true);

                    $this->processCollection(
                        $sqlConnection,
                        $mongoConnection,
                        $first,
                        $rel->foreign1_fields
                    );
                    $end_time = microtime(true);

                    // Calculate the Script Execution Time
                    $execution_time = ($end_time - $start_time);

                    dd('processCollection finished', $execution_time);

                    // 2. Second
                    // $second = $rel->collection2;
                    // $this->processCollection($sqlConnection, $second, $rel->foreign2_fields);




                    dd($record);


                    // return false; // stop
                });
        }

        dd('done');
        // return 'done';
    }

    private function processCollection(
        $sqlConnection,
        $mongoConnection,
        Collection $collection,
        array $identificationСolumns = null
    ) {
        // 1. Load and transform fields
        // 2. Process links
        // 3. Process embedds (recursively)
        // 4. Return result

        $table = $collection->sqlTable;

        $sqlConnection->table($table->name)
            ->orderBy($table->getOrderingColumnName())
            ->lazy()
            ->each(function (object $recordObj) use (
                $collection,
                $table,
                $sqlConnection,
                $mongoConnection,
                &$identificationСolumns,
            ) {
                $record = $this->processRecord($recordObj, $collection, $sqlConnection);

                $this->createIdMapping(
                    $table,
                    $collection,
                    $recordObj,
                    $record,
                    $identificationСolumns
                );

                // ----------------------------------------------------------------------
                // потенційно здоровенний масив ...
                $this->writeToMongo($mongoConnection, $collection, $record);
            });
    }

    public function processRecord(
        object $recordObj,
        $collection,
        $sqlConnection,
        $isEmbedding = false,
        $currentDepth = 0,
    ) {
        $sqlTable = $collection->sqlTable;
        $linksEmbeddsFrom = $collection->linksEmbeddsFrom;
        $fields = $collection->fields;

        // Перевірка на досягнення максимальної глибини
        if ($currentDepth > $this->maxEmbeddingDepth) {
            // Якщо максимальна глибина досягнута, повертаємо запис без подальшої обробки вкладень
            return (array) $recordObj;
        }

        $record = (array) $recordObj;

        // Обробка полів
        foreach ($fields as $field) {
            $record[$field->name] = Converter::convert($record[$field->name], $field->type);
        }
        $record = $this->addObjectId($record);

        // Обробка зв'язків типу "linking"
        foreach ($linksEmbeddsFrom->where('relation_type', MongoRelationType::LINKING) as $link) {
            // можна зв'язок так і залишити
            // на випадок невідповідності типів даних у полях - взяти типи даних з foreign fields
            $pkCollection = $link->pkCollection()->with(['fields'])->first();
            $foreignFields = $pkCollection->fields->filter(function ($field) use ($link) {
                return in_array($field->name, $link->foreign_fields);
            });

            foreach ($foreignFields as $key => $foreignField) {
                $localField = $link->local_fields[$key];
                $record[$localField] = Converter::convert($recordObj->$localField, $foreignField->type);
            }
        }

        // Обробка зв'язків типу "embedding"
        foreach ($linksEmbeddsFrom->where('relation_type', MongoRelationType::EMBEDDING) as $embed) {
            $relatedCollection = $embed->pkCollection()->with(['sqlTable', 'fields', 'linksEmbeddsFrom'])->first();
            $relatedTable = $relatedCollection->sqlTable;

            $relatedFields = $relatedCollection->fields;
            $relatedLinksEmbedds = $relatedCollection->linksEmbeddsFrom;

            // Завантаження пов'язаних записів і обробка кожного з них
            $embeddedRecords = [];

            $query = $sqlConnection->table($relatedTable->name);
            foreach ($embed->local_fields as $key => $localFk) {
                $query->whereColumn($localFk, $embed->foreign_fields[$key]);
            }

            $query->lazy()
                ->each(function (object $relatedRecordObj) use (
                    &$embeddedRecords,
                    $relatedCollection,
                    $relatedFields,
                    $relatedLinksEmbedds,
                    $sqlConnection,
                    $currentDepth
                ) {
                    // Рекурсивний виклик для обробки вкладених записів
                    $embeddedRecord = $this->processRecord(
                        $relatedRecordObj,
                        $relatedCollection,
                        $relatedFields,
                        $relatedLinksEmbedds,
                        $sqlConnection,
                        true, // Вказуємо, що це вкладення
                        $currentDepth + 1 // Збільшуємо поточну глибину
                    );

                    // Додаємо оброблений запис до масиву вкладень
                    $embeddedRecords[] = $embeddedRecord;
                });

            // Додаємо вкладення до поточного запису
            $record[$relatedCollection->name . '_embed'] = $embeddedRecords;
        }

        return $record;

        // // Якщо це головний запис, зберігаємо його в MongoDB, інакше повертаємо результат
        // if (!$isEmbedding) {
        //     // Зберегти в MongoDB
        //     dd('write', $record);

        //     // writeToMongoDB($collection->mongoCollectionName, $record);
        // } else {
        //     // Повернути оброблений запис для вкладення
        //     return $record;
        // }
    }

    private function addObjectId(array $record): array
    {
        return array_merge(['_id' => Converter::createObjectId()], $record);
    }

    private function createIdMapping(
        $table,
        $collection,
        $recordObj,
        array &$record,
        array &$identificationСolumns
    ) {
        if (! is_null($identificationСolumns) && ! empty($identificationСolumns)) {
            return IdMapping::create([
                'table_id' => $table->id,
                'collection_id' => $collection->id,
                'source_data' => array_intersect_key(
                    (array)$recordObj,
                    array_flip($identificationСolumns)
                ),
                'mapped_id' => $record['_id']->__toString(),
            ]);
        }
    }

    private function writeToMongo($mongoConnection, $collection, $record)
    {
        return $mongoConnection
            ->collection($collection->name)
            ->insert($record);
    }
}
