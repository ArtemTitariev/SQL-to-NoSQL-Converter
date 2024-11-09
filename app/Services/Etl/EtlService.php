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

    // public static  function processCollection(
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
    //             $record = $this->processDocument($recordObj, $collection);
    //             $mainDocumentId = $this->writeToMongo($mongoConnection, $collection, $record);

    //             // 2. Обробка вкладень
    //             $this->processEmbeds($mainDocumentId, $recordObj, $collection, $sqlConnection, $mongoConnection);

    //             $this->createIdMapping(
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

    // public static function processPivotCollection(
    //     Collection $pivot,
    //     Collection $first,
    //     Collection $second,
    //     ManyToManyLink $relation,
    //     $sqlConnection,
    //     $mongoConnection,
    // ) {

    //     switch ($relation->relation_type) {
    //         case MongoManyToManyRelation::LINKING_WITH_PIVOT:
    //             static::saveAsLinkWithPivot(
    //                 $pivot,
    //                 $first,
    //                 $second,
    //                 $relation,
    //                 $sqlConnection,
    //                 $mongoConnection
    //             );
    //             break;
    //         case MongoManyToManyRelation::EMBEDDING:
    //             static::saveAsEmbedding(
    //                 $pivot,
    //                 $first,
    //                 $second,
    //                 $relation,
    //                 $sqlConnection,
    //                 $mongoConnection
    //             );
    //             break;
    //         case MongoManyToManyRelation::HYBRID:
    //             static::saveAsHybrid(
    //                 $pivot,
    //                 $first,
    //                 $second,
    //                 $relation,
    //                 $sqlConnection,
    //                 $mongoConnection
    //             );
    //             break;
    //         default:
    //             break;
    //     }
    // }

    // public static function saveAsLinkWithPivot(
    //     Collection $pivot,
    //     Collection $first,
    //     Collection $second,
    //     ManyToManyLink $relation,
    //     $sqlConnection,
    //     $mongoConnection,
    // ) {
    //     // LINK WITH PIVOT
    //     // 1. Process pivot like ordinary collection
    //     // using IdMapping

    //     static::processCollection( // As job
    //         $pivot,
    //         $sqlConnection,
    //         $mongoConnection
    //     );

    //     static::syncMainIdsWithMapping(
    //         $pivot,
    //         $first,
    //         $relation->foreign1_fields,
    //         $relation->local1_fields,
    //         $sqlConnection,
    //         $mongoConnection,
    //     );

    //     static::syncMainIdsWithMapping(
    //         $pivot,
    //         $second,
    //         $relation->foreign2_fields,
    //         $relation->local2_fields,
    //         $sqlConnection,
    //         $mongoConnection,
    //     );
    // }

    public static function syncMainIdsWithMapping(
        Collection $pivotCollection,
        Collection $mainCollection,
        $foreignFields,
        $localFields,
        $sqlConnection,
        $mongoConnection,
    ) {

        $pivotTable = $pivotCollection->sqlTable;
        $mainTable = $mainCollection->sqlTable;

        // load all ids from the first table using in pivot
        $firstIds = $sqlConnection->table($pivotTable->name . ' as pivot')
            ->select(array_map(fn($field) => "left.{$field}", $foreignFields))
            ->distinct()
            ->leftJoin($mainTable->name . ' as left', function ($join) use ($foreignFields, $localFields) {
                foreach ($foreignFields as $key => $foreignField) {
                    $join->on("left.{$foreignField}", '=', "pivot.{$localFields[$key]}");
                }
            })->get();


        foreach ($firstIds as $id) {
            $id = (array)$id;
            $firstMapping = IdMapping::where('table_id', $mainTable->id)
                ->where('collection_id', $mainCollection->id)
                ->where('source_data_hash', IdMapping::makeHash($id))
                ->first();

            if (!$firstMapping) {
                continue;
            }

            // Оновлення зв'язків у MongoDB
            $mongoConnection->collection($pivotCollection->name)
                ->where(function ($query) use ($foreignFields, $localFields, $id) {
                    foreach ($foreignFields as $key => $field) {
                        $query->where($localFields[$key], $id[$field]);
                    }
                })->update([
                    Str::singular($mainTable->name) . '_id' => new ObjectId($firstMapping->mapped_id),
                ]);
        }
    }

    // public static function saveAsEmbedding(
    //     Collection $pivot,
    //     Collection $first,
    //     Collection $second,
    //     ManyToManyLink $relation,
    //     $sqlConnection,
    //     $mongoConnection,
    // ) {
    //     // EMBEDDING

    //     // First
    //     static::embedDocumentsForCollection(
    //         $pivot,
    //         $first,
    //         $second,
    //         $relation->local1_fields,
    //         $relation->local2_fields,
    //         $relation->foreign2_fields,
    //         $sqlConnection,
    //         $mongoConnection
    //     );

    //     // Second
    //     static::embedDocumentsForCollection(
    //         $pivot,
    //         $second,
    //         $first,
    //         $relation->local2_fields,
    //         $relation->local1_fields,
    //         $relation->foreign1_fields,
    //         $sqlConnection,
    //         $mongoConnection
    //     );
    // }

    public static function embedDocumentsForCollection(
        Collection $pivot,
        Collection $first,
        Collection $second,
        $local1Fields,
        $local2Fields,
        $foreign2Fields,
        $sqlConnection,
        $mongoConnection,
    ) {
        // 1. For each document from first main collection
        $mongoConnection->collection($first->name)
            ->orderBy("_id")
            ->project(['_id' => 1])
            ->lazy()
            ->each(function (array $mainRecord) use (
                $pivot,
                $first,
                $second,
                $local1Fields,
                $local2Fields,
                $foreign2Fields,
                $sqlConnection,
                $mongoConnection,
            ) {

                $data = static::getMappedData(
                    $pivot,
                    $first,
                    $second,
                    $local1Fields,
                    $local2Fields,
                    $foreign2Fields,
                    $mainRecord["_id"],
                    $sqlConnection,
                );

                $mappedSecondIds = $data['mappedSecondIds'];
                $metaArray = $data['metaArray'];
                unset($data);

                // 5. Get related document from second colection, push to first
                $mongoConnection->collection($second->name)
                    ->whereIn('_id', $mappedSecondIds)
                    ->orderBy("_id")
                    ->lazy()
                    ->each(function (array $embeddedRecord) use (
                        $first,
                        $second,
                        $mainRecord,
                        $mongoConnection,
                        &$metaArray,
                    ) {
                        $metaData = array_shift($metaArray) ?? [];
                        $embeddedRecordWithMeta = $metaData ? array_merge(
                            $embeddedRecord,
                            ['meta' => $metaData]
                        ) : $embeddedRecord;

                        $mongoConnection
                            ->collection($first->name)
                            ->where('_id', $mainRecord['_id'])
                            ->push($second->name, $embeddedRecordWithMeta);
                    });
            });
    }

    // public static function saveAsHybrid(
    //     Collection $pivot,
    //     Collection $first,
    //     Collection $second,
    //     ManyToManyLink $relation,
    //     $sqlConnection,
    //     $mongoConnection,
    // ) {
    //     // HYBRID

    //     // First
    //     static::linkDocumentsForCollection(
    //         $pivot,
    //         $first,
    //         $second,
    //         $relation->local1_fields,
    //         $relation->local2_fields,
    //         $relation->foreign2_fields,
    //         $sqlConnection,
    //         $mongoConnection
    //     );

    //     // Second
    //     static::linkDocumentsForCollection(
    //         $pivot,
    //         $second,
    //         $first,
    //         $relation->local2_fields,
    //         $relation->local1_fields,
    //         $relation->foreign1_fields,
    //         $sqlConnection,
    //         $mongoConnection
    //     );
    // }

    public static function linkDocumentsForCollection(
        Collection $pivot,
        Collection $first,
        Collection $second,
        $local1Fields,
        $local2Fields,
        $foreign2Fields,
        $sqlConnection,
        $mongoConnection,
    ) {
        // 1. For each document from first main collection
        $mongoConnection->collection($first->name)
            ->orderBy("_id")
            ->project(['_id' => 1])
            ->lazy()
            ->each(function (array $mainRecord) use (
                $pivot,
                $first,
                $second,
                $local1Fields,
                $local2Fields,
                $foreign2Fields,
                $sqlConnection,
                $mongoConnection,
            ) {
                $data = static::getMappedData(
                    $pivot,
                    $first,
                    $second,
                    $local1Fields,
                    $local2Fields,
                    $foreign2Fields,
                    $mainRecord["_id"],
                    $sqlConnection,
                );

                $mappedSecondIds = $data['mappedSecondIds'];
                $metaArray = $data['metaArray'];
                unset($data);

                $query = $mongoConnection
                    ->collection($first->name)
                    ->where('_id', $mainRecord['_id']);

                // 5. Push Mongo ids (from second) to first
                if (!empty($metaArray)) { // && count($metaArray)
                    $result = array_map(function ($id, $meta) {
                        return [
                            "_id" => $id,
                            "meta" => $meta,
                        ];
                    }, $mappedSecondIds, $metaArray);

                    $query->push($second->name . '_links', $result);
                } else {
                    // Якщо метаданих немає, просто вставляємо запис
                    $query->push($second->name . '_links', $mappedSecondIds);
                }
            });
    }

    public static function getMappedData(
        Collection $pivot,
        Collection $first,
        Collection $second,
        $local1Fields,
        $local2Fields,
        $foreign2Fields,
        $mainRecordId,
        $sqlConnection,
    ) {
        $pivotTable = $pivot->sqlTable;

        // 2. Get mapped sql id from first table
        $sqlIdArray = IdMapping::where('collection_id', $first->id)
            ->where('table_id', $first->sql_table_id)
            ->where('mapped_id', $mainRecordId)
            ->first(['source_data'])?->source_data;

        $sqlIdArray = array_values($sqlIdArray);

        // 3. Select second_ids (or more fields) from sql pivot 
        $query = $sqlConnection
            ->table($pivotTable->name)
            ->where(function ($query) use ($local1Fields, $sqlIdArray) {
                foreach ($local1Fields as $index => $field) {
                    $query->where($field, $sqlIdArray[$index]);
                }
            });

        // Отримуємо мета-поля
        $metaFields = $pivot->getMetaFieldsOnPivot();
        $fieldsToSelect = array_merge($local2Fields, $metaFields->pluck('name')->toArray());

        // Отримання SQL ID і мета-даних (якщо вони є)
        $relatedSqlIdsWithMeta = $query->get($fieldsToSelect);

        // Формування хешованих ID та додавання мета-даних (якщо вони є)
        $relatedHashedSqlIds = $relatedSqlIdsWithMeta->map(function ($record) use ($foreign2Fields, $metaFields) {
            // Перетворюємо результат на масив для зручності доступу
            $recordArray = (array) $record;
            $id = array_values(array_slice($recordArray, 0, count($foreign2Fields)));

            // Створюємо хешований ID
            $hashedId = IdMapping::makeHash(
                array_combine($foreign2Fields, $id)
            );

            // Додаємо мета-дані, якщо вони існують
            $metaData = [];
            if ($metaFields->isNotEmpty()) {
                $metaData = $metaFields->mapWithKeys(function ($field) use ($recordArray) {
                    $value = $recordArray[$field->name] ?? null;
                    return [
                        $field->name => is_null($value) ? null :
                            Converter::convert($value, $field->type)
                    ];
                })->toArray();
            }

            return [
                'hashed_id' => $hashedId,
                'meta_data' => $metaData,
            ];
        })->toArray();

        //  4. Get mapped Mongo ids from second main collection
        $mappedSecondIds = IdMapping::where('collection_id', $second->id)
            ->where('table_id', $second->sql_table_id)
            ->whereIn('source_data_hash', array_column($relatedHashedSqlIds, 'hashed_id'))
            ->pluck('mapped_id')->toArray();

        $metaArray = array_filter(array_column($relatedHashedSqlIds, 'meta_data'));

        return [
            'mappedSecondIds' => $mappedSecondIds,
            'metaArray' => $metaArray,
        ];
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
