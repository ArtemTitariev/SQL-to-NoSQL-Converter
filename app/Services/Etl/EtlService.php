<?php

namespace App\Services\Etl;

use App\Enums\MongoManyToManyRelation;
use App\Enums\MongoRelationType;
use App\Models\Convert;
use App\Models\IdMapping;
use App\Models\MongoSchema\Collection;
use App\Models\MongoSchema\ManyToManyLink;
use App\Models\SQLSchema\Table;
use App\Services\DataTypes\Converter;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use MongoDB\BSON\ObjectId;

class EtlService
{
    const maxEmbeddingDepth = 5;

    public static function test(
        Convert $convert,
    ) {
        $n = $convert->sqlDatabase()->get();
    }

    // public static function processCollection(
    //     Collection $collection,
    //     $sqlConnection,
    //     $mongoConnection,
    //     array $identificationСolumns = null
    // ) {
    //     // 1. Load and transform fields
    //     // 2. Process links
    //     // 3. Process embedds (recursively)
    //     // 4. Save result

    //     $table = $collection->sqlTable;

    //     $sqlConnection->table($table->name)
    //         ->orderBy($table->getOrderingColumnName())
    //         ->lazy()
    //         ->each(function (object $recordObj) use (
    //             $collection,
    //             $table,
    //             $sqlConnection,
    //             $mongoConnection,
    //             &$identificationСolumns,
    //         ) {

    //             // 1. Запис головного (батьківського) документа
    //             $record = static::processDocument($recordObj, $collection);
    //             $mainDocumentId = static::writeToMongo($mongoConnection, $collection, $record);

    //             // 2. Обробка вкладень
    //             static::processEmbeds($mainDocumentId, $recordObj, $collection, $sqlConnection, $mongoConnection);

    //             static::createIdMapping(
    //                 $table,
    //                 $collection,
    //                 $mainDocumentId,
    //                 $recordObj,
    //                 $identificationСolumns
    //             );
    //         });
    // }

    public static function processDocument(
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
        $record = static::addObjectId($record);

        // Обробка зв'язків типу "linking"
        foreach ($linksEmbeddsFrom->where('relation_type', MongoRelationType::LINKING) as $link) {
            // можна зв'язок так і залишити
            // на випадок невідповідності типів даних у полях - взяти типи даних з foreign fields
            $pkCollection = $link->pkCollection()->with(['fields'])->first();
            $foreignFields = $pkCollection->fields->filter(function ($field) use ($link) {
                return in_array($field->name, $link->foreign_fields);
            })->values();

            foreach ($foreignFields as $key => $foreignField) {
                $localField = $link->local_fields[$key];
                $record[$localField] = Converter::convert($recordObj->$localField, $foreignField->type);
            }
        }

        return $record;
    }

    public static function processEmbeds(
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
        while ($currentLevel->isNotEmpty() && $currentDepth < static::maxEmbeddingDepth) {
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

                            $embeddedRecord = static::processDocument(
                                $relatedRecordObj,
                                $relatedCollection,
                            );

                            // Додаємо новий документ у вкладення
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


    public static function addObjectId(array $record): array
    {
        return array_merge(['_id' => Converter::createObjectId()], $record);
    }

    public static function createIdMapping(
        Table $table,
        Collection $collection,
        ObjectId $documentId,
        $recordObj,
        ?array &$identificationСolumns
    ) {
        if (! is_null($identificationСolumns) && ! empty($identificationСolumns)) {
            return IdMapping::create([
                'table_id' => $table->id,
                'collection_id' => $collection->id,
                'source_data' => array_intersect_key(
                    (array)$recordObj,
                    array_flip($identificationСolumns)
                ),
                'mapped_id' => $documentId->__toString(),
            ]);
        }
    }

    public static function writeToMongo($mongoConnection, Collection $collection, $record)
    {
        return $mongoConnection
            ->collection($collection->name)
            ->insertGetId($record);
    }

}