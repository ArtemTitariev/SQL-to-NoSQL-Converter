<?php

namespace App\Http\Controllers;

use App\Enums\MongoManyToManyRelation;
use App\Enums\MongoRelationType;
use App\Enums\RelationType;
use App\Models\Convert;
use App\Models\IdMapping;
use App\Models\MongoSchema\Collection;

use App\Services\DatabaseConnections\ConnectionCreator;
use App\Services\DataTypes\Converter;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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

        $mongoConnection->dropCollection('tags2'); //------------------------------------------------------------------------------

        // $collections = $mongoDatabase->collections()->with(['fields', 'linksEmbeddsFrom', 'manyToManyPivot'])
        //     ->whereHas('manyToManyPivot')
        //     ->whereDoesntHave('linksEmbeddsTo')
        //     ->whereDoesntHave('manyToManyFirst')
        //     ->whereDoesntHave('manyToManySecond')
        //     ->get();

        $collections = $mongoDatabase->collections()->with(['fields', 'linksEmbeddsFrom', 'manyToManyPivot'])
            ->where('name', 'post_tag2')
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
                    $first = $rel->collection2()->with(['fields'])->first(); //----- collection1()

                    $start_time = microtime(true);

                    $this->processCollection(
                        $first,
                        $sqlConnection,
                        $mongoConnection,
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
        Collection $collection,
        $sqlConnection,
        $mongoConnection,
        array $identificationСolumns = null
    ) {
        // 1. Load and transform fields
        // 2. Process links
        // 3. Process embedds (recursively)
        // 4. Save result

        $table = $collection->sqlTable;

        $sqlConnection->table($table->name)
            ->orderBy($table->getOrderingColumnName())
            ->lazy()
            ->each(function (object $recordObj) use (
                $collection,
                $sqlConnection,
                $mongoConnection,
                &$identificationСolumns,
            ) {

                // 1. Запис головного (батьківського) документа
                $record = $this->processDocument($recordObj, $collection);
                $mainDocumentId = $this->writeToMongo($mongoConnection, $collection, $record);

                // $parentDoc = $mongoConnection
                //     ->collection($collection->name)
                //     ->where('_id', $mainDocumentId)
                //     ->first();

                // 2. Обробка вкладень
                $this->processEmbeds($mainDocumentId, $recordObj, $collection, $sqlConnection, $mongoConnection);

                // dd('FIRST PROCESSED');
                // $record = $this->processDocument($recordObj, $collection, $sqlConnection);

                // $this->createIdMapping(
                //     $table,
                //     $collection,
                //     $recordObj,
                //     $record,
                //     $identificationСolumns
                // );
            });
    }

    public function processDocument(
        object $recordObj,
        $collection,
    ) {
        $linksEmbeddsFrom = $collection->linksEmbeddsFrom;
        $fields = $collection->fields;

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

        // $embedds = $linksEmbeddsFrom->where('relation_type', MongoRelationType::EMBEDDING)->where('embed_in_main', true);
        // $embedds = $embedds->merge($collection->linksEmbeddsTo->where('relation_type', MongoRelationType::EMBEDDING)->where('embed_in_main', false));
        // // Обробка зв'язків типу "embedding"
        // foreach ($embedds as $embed) {


        //     $relatedCollection = $embed->embed_in_main ?
        //         $embed->pkCollection()->with(['sqlTable', 'fields', 'linksEmbeddsFrom'])->first() :
        //         $embed->fkCollection()->with(['sqlTable', 'fields', 'linksEmbeddsFrom'])->first();

        //     $localFields = $embed->embed_in_main ?
        //         $embed->local_fields :
        //         $embed->foreign_fields;


        //     $foreignFields = $embed->embed_in_main ?
        //         $embed->foreign_fields :
        //         $embed->local_fields;

        //     $relatedTable = $relatedCollection->sqlTable;

        //     // $relatedFields = $relatedCollection->fields;
        //     // $relatedLinksEmbedds = $relatedCollection->linksEmbeddsFrom;

        //     // Завантаження пов'язаних записів і обробка кожного з них
        //     $embeddedRecords = [];

        //     $query = $sqlConnection->table($relatedTable->name);
        //     foreach ($localFields as $key => $localFk) {
        //         $query->where($foreignFields[$key], $recordObj->$localFk);
        //     }

        //     $query->orderBy($relatedTable->getOrderingColumnName())
        //         ->lazy()
        //         ->each(function (object $relatedRecordObj) use (
        //             &$embeddedRecords,
        //             $relatedCollection,
        //             // $relatedFields,
        //             // $relatedLinksEmbedds,
        //             $sqlConnection,
        //             $currentDepth
        //         ) {
        //             // Рекурсивний виклик для обробки вкладених записів
        //             $embeddedRecord = $this->processDocument(
        //                 $relatedRecordObj,
        //                 $relatedCollection,
        //                 // $relatedFields,
        //                 // $relatedLinksEmbedds,
        //                 $sqlConnection,
        //                 true, // Вказуємо, що це вкладення
        //                 $currentDepth + 1 // Збільшуємо поточну глибину
        //             );

        //             // Додаємо оброблений запис до масиву вкладень
        //             $embeddedRecords[] = $embeddedRecord;
        //         });

        //     // Додаємо вкладення до поточного запису
        //     $record[$relatedCollection->name . '_embed'] = $embeddedRecords;
        // }

        return $record;
    }

    private function processEmbeds(
        $mainDocumentId,
        object $recordObj,
        Collection $mainCollection,
        $sqlConnection,
        $mongoConnection,
    ) {
        // Перший рівень вкладень починається з основного запису
        $currentLevel = collect([['record' => $recordObj, 'path' => '', 'parentCollection' => $mainCollection]]);
        $currentDepth = 0;

        // Поки є дані на поточному рівні, продовжуємо обробку
        while ($currentLevel->isNotEmpty() && $currentDepth < $this->maxEmbeddingDepth) {
            $nextLevel = collect();

            foreach ($currentLevel as $item) {
                $record = $item['record'];
                $path = $item['path'];
                $parentCollection = $item['parentCollection'];

                // Отримуємо зв'язки для вкладень для поточного рівня
                $embedds = $parentCollection->linksEmbeddsFrom
                    ->where('relation_type', MongoRelationType::EMBEDDING)
                    ->where('embed_in_main', true)
                    ->merge(
                        $parentCollection->linksEmbeddsTo
                            ->where('relation_type', MongoRelationType::EMBEDDING)
                            ->where('embed_in_main', false)
                    );

                foreach ($embedds as $embed) {
                    $relatedCollection = $embed->embed_in_main
                        ? $embed->pkCollection()->with(['sqlTable', 'fields', 'linksEmbeddsFrom'])->first()
                        : $embed->fkCollection()->with(['sqlTable', 'fields', 'linksEmbeddsFrom'])->first();

                    $localFields = $embed->embed_in_main ? $embed->local_fields : $embed->foreign_fields;
                    $foreignFields = $embed->embed_in_main ? $embed->foreign_fields : $embed->local_fields;
                    $relatedTable = $relatedCollection->sqlTable;

                    // Завантажуємо пов'язані записи для поточного рівня
                    $query = $sqlConnection->table($relatedTable->name);
                    foreach ($localFields as $index => $localFk) {
                        $query->where($foreignFields[$index], $record->$localFk);
                    }

                    $query->orderBy($relatedTable->getOrderingColumnName())
                        ->lazy()
                        ->each(function (object $relatedRecordObj) use (
                            $mainDocumentId,
                            $mainCollection,
                            $relatedCollection,
                            $sqlConnection,
                            &$nextLevel,
                            $mongoConnection,
                            $path,
                        ) {
                            // Отримуємо батьківський документ і перевіряємо існування шляху
                            $parentDoc = $mongoConnection
                                ->collection($mainCollection->name)
                                ->where('_id', $mainDocumentId)
                                ->first();

                            if ($path === '') {
                                $path .= $relatedCollection->name;
                            } else {
                                $path .= ".{$relatedCollection->name}";
                            }

                            // Якщо це перший рівень вкладення, створюємо новий масив
                            $nestedArray = data_get($parentDoc, $path) ?? [];

                            $embeddedRecord = $this->processDocument(
                                $relatedRecordObj,
                                $relatedCollection,
                            );

                            // Додаємо новий кодумент у вкладення
                            $mongoConnection
                                ->collection($mainCollection->name)
                                ->where('_id', $mainDocumentId)
                                ->push($path, $embeddedRecord);

                            // Знаходимо індекс доданого
                            $newIndex = count($nestedArray);

                            // Оновлюємо шлях для наступного рівня ітерації
                            $nextLevel->push([
                                'record' => $relatedRecordObj,
                                'path' => "{$path}.{$newIndex}",
                                'parentCollection' => $relatedCollection,
                            ]);
                        });
                }
            }

            // Перехід до наступного рівня
            $currentLevel = $nextLevel;
            $currentDepth++;
        }
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
            ->insertGetId($record);
    }
}
