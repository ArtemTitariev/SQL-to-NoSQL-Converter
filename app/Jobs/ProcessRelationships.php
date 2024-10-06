<?php

namespace App\Jobs;

use App\Actions\CompleteProcessRelationshipsStep;
use App\Enums\EventStatus;
use App\Enums\MongoManyToManyRelation;
use App\Enums\MongoRelationType;
use App\Enums\RelationType;
use App\Events\ProcessRelationships as ProcessRelationshipsEvent;
use App\Models\Convert;
use App\Models\User;
use App\Models\MongoSchema\LinkEmbedd;
use App\Models\MongoSchema\ManyToManyLink;
use App\Models\SQLSchema\CircularRef;
use App\Models\SQLSchema\ForeignKey;
use App\Models\SQLSchema\Table;
use App\Services\ConversionService;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * --------------------------------------------
 * Чи варто аналізувати зв'язки "вглиб" ???
 * --------------------------------------------
 */

class ProcessRelationships implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The job may be attempted only once
     *
     * @var int
     */
    public $tries = 1;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public User $user,
        public Convert $convert,
        public string $step,
    ) {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $sqlDatabase = $this->convert->sqlDatabase()
            ->with(['circularRefs'])
            ->first();
        $mongoDatabase = $this->convert->mongoDatabase()
            ->with(['collections'])
            ->first();

        try {
            $collections = $mongoDatabase->collections()
                ->with(['linksEmbeddsFrom', 'linksEmbeddsTo', 'manyToManyPivot'])
                ->get();

            // Перший запит: отримуємо таблиці з many-to-many зв'язками
            $manyToManyTables = $sqlDatabase->tables()
                ->whereIn('name', $collections->pluck('name'))
                ->with(['columns', 'foreignKeys'])
                ->whereHas('foreignKeys', function ($query) {
                    $query->where('relation_type', RelationType::MANY_TO_MANY);
                })->get();

            // Другий запит: отримуємо всі таблиці, які не мають many-to-many зв'язків
            $otherTables = $sqlDatabase->tables()
                ->whereIn('name', $collections->pluck('name'))
                ->with(['columns', 'foreignKeys'])
                ->whereDoesntHave('foreignKeys', function ($query) {
                    $query->where('relation_type', RelationType::MANY_TO_MANY);
                })
                ->get();


            $hasRelations = ! $manyToManyTables->empty();

            $this->processManyToManyRelations($sqlDatabase, $manyToManyTables, $collections);

            foreach ($otherTables as $table) {
                $foreignKeys = $table->foreignKeys;
                if ($foreignKeys->isEmpty()) {
                    continue;
                }

                $hasRelations = true;

                // $rel = [];
                // $rel[$table->name] = ['link' => [], 'emb' => []];

                // Перевірити, чи не вкладена ця колекція
                if (LinkEmbedd::join('collections as pk_collections', 'links_embedds.pk_collection_id', '=', 'pk_collections.id')
                    ->where('pk_collections.mongo_database_id', $mongoDatabase->id)
                    ->where('pk_collections.name', $table->name)
                    ->where('links_embedds.relation_type', MongoRelationType::EMBEDDING)
                    ->exists()
                ) {
                    // dd('Unexpected embedding', $table->name); //--------------
                    throw new \UnexpectedValueException("Unexpected embedding: {$table->name}");
                }

                foreach ($foreignKeys as $fk) {

                    $related = $fk->relatedTable($sqlDatabase->id, true);

                    switch ($fk->relation_type) {
                        case RelationType::ONE_TO_ONE:
                            if (
                                $related->foreignKeys->isEmpty() && // Якщо немає зовнішніх ключів

                                ! CircularRef::checkIfExistsByTableName($sqlDatabase->id, $related->name) && // Якщо не в круговому з'єднанні

                                ! ForeignKey::relationToTableExists($sqlDatabase->id, [$related->name], [$fk->id]) && // Якщо в інших немає посилань на related

                                // І якщо немає linking з іншими -----------------
                                ! LinkEmbedd::join('collections as fk_collections', 'links_embedds.fk_collection_id', '=', 'fk_collections.id')
                                    ->join('collections as pk_collections', 'links_embedds.pk_collection_id', '=', 'pk_collections.id')
                                    ->where(function ($query) use ($mongoDatabase, $related) {
                                        $query->where('fk_collections.mongo_database_id', $mongoDatabase->id)
                                            ->where('fk_collections.name', $related->name)
                                            ->where('links_embedds.relation_type', MongoRelationType::LINKING);
                                    })
                                    ->orWhere(function ($query) use ($mongoDatabase, $related) {
                                        $query->where('pk_collections.mongo_database_id', $mongoDatabase->id)
                                            ->where('pk_collections.name', $related->name)
                                            ->where('links_embedds.relation_type', MongoRelationType::LINKING);
                                    })
                                    ->exists()

                            ) {
                                // Embedding
                                LinkEmbedd::createEmbedding($table, $fk, $collections);
                                // $rel[$table->name]['emb'][] = ['related' => $related->name, 'reason' => 'no FK and no circular refs'];
                            } else {
                                // Та, яка пов'язується, і та, до якої пов'язуються, не мають бути вкладеними ------------------------------------------------!!!
                                // перша умова перевіряється на початку (та, яка пов'язується - не вкладена)

                                // тут перевірка, щоб та, до якої пов'язується не була вкладена
                                if (LinkEmbedd::join('collections as pk_collections', 'links_embedds.pk_collection_id', '=', 'pk_collections.id')
                                    ->where('pk_collections.mongo_database_id', $mongoDatabase->id)
                                    ->where('pk_collections.name', $related->name)
                                    ->where('links_embedds.relation_type', MongoRelationType::EMBEDDING)
                                    ->exists()
                                ) {
                                    // dd('Unexpected embedding.... Його тут не має бути');
                                    throw new \UnexpectedValueException("Unexpected embedding (1): {$table->name}");
                                }

                                // Linking
                                LinkEmbedd::createLink($table, $fk, $collections);
                                // $rel[$table->name]['link'][] = ['related' => $related->name, 'reason' => 'FK OR circular refs'];
                            }
                            break;
                        case RelationType::MANY_TO_ONE:
                            if (
                                $related->foreignKeys->isEmpty() && // Якщо немає зовнішніх ключів

                                ! CircularRef::checkIfExistsByTableName($sqlDatabase->id, $related->name) && // Якщо не в круговому з'єднанні

                                ! ForeignKey::relationToTableExists($sqlDatabase->id, [$related->name], [$fk->id]) && // Якщо в інших немає посилань на realted

                                isWithinRowNumberLimit($related->rows_number) && // Якщо записів небагато

                                // І якщо немає linking з іншими -----------------
                                ! LinkEmbedd::join('collections as fk_collections', 'links_embedds.fk_collection_id', '=', 'fk_collections.id')
                                    ->join('collections as pk_collections', 'links_embedds.pk_collection_id', '=', 'pk_collections.id')
                                    ->where(function ($query) use ($mongoDatabase, $related) {
                                        $query->where('fk_collections.mongo_database_id', $mongoDatabase->id)
                                            ->where('fk_collections.name', $related->name)
                                            ->where('links_embedds.relation_type', MongoRelationType::LINKING);
                                    })
                                    ->orWhere(function ($query) use ($mongoDatabase, $related) {
                                        $query->where('pk_collections.mongo_database_id', $mongoDatabase->id)
                                            ->where('pk_collections.name', $related->name)
                                            ->where('links_embedds.relation_type', MongoRelationType::LINKING);
                                    })
                                    ->exists()

                            ) {
                                // Embedding
                                LinkEmbedd::createEmbedding($table, $fk, $collections);
                                // $rel[$table->name]['emb'][] = ['related' => $related->name, 'reason' => 'no FK and no circular refs and rows_number <= 100'];
                            } else {

                                // Та, яка пов'язується, і та, до якої пов'язуються, не мають бути вкладеними ------------------------------------------------!!!
                                // перша умова перевіряється на початку (та, яка пов'язується - не вкладена)

                                // тут перевірка, щоб та, до якої пов'язується не була вкладена
                                if (LinkEmbedd::join('collections as pk_collections', 'links_embedds.pk_collection_id', '=', 'pk_collections.id')
                                    ->where('pk_collections.mongo_database_id', $mongoDatabase->id)
                                    ->where('pk_collections.name', $related->name)
                                    ->where('links_embedds.relation_type', MongoRelationType::EMBEDDING)
                                    ->exists()
                                ) {
                                    // dd('Unexpected embedding.... Чому? Його тут не має бути');
                                    throw new \UnexpectedValueException("Unexpected embedding (2): {$table->name}");
                                }

                                // Linking
                                LinkEmbedd::createLink($table, $fk, $collections);
                                // $rel[$table->name]['link'][] = ['related' => $related->name, 'reason' => 'FK OR circular refs or num_rows > 100'];
                            }
                            break;
                        case RelationType::SELF_REF:
                            // Link
                            LinkEmbedd::createLink($table, $fk, $collections);
                            // $rel[$table->name]['link'][] = ['related' => $fk->foreign_table, 'reason' => 'self ref'];
                            break;
                        case RelationType::COMPLEX:
                            // Link
                            LinkEmbedd::createLink($table, $fk, $collections);
                            // $rel[$table->name]['link'][] = ['related' => $fk->foreign_table, 'reason' => 'complex'];
                            break;

                            // case RelationType::ONE_TO_MANY:
                            //     // Їх тут не має бути
                            //     $this->throwInvalidRelationType($fk->relation_type->value);
                            //     break;
                            // case RelationType::MANY_TO_MANY:
                            //     // Тут їх не має бути
                            //     $this->throwInvalidRelationType($fk->relation_type->value);
                            //     // break;
                        default:
                            $this->throwInvalidRelationType($fk->relation_type->value);
                    }
                }
            }

            CompleteProcessRelationshipsStep::execute($this->convert, $this->step, $hasRelations);

            // Трансляція події про успішне завершення
            ProcessRelationshipsEvent::dispatch(
                $this->user->id,
                $this->convert->id,
                EventStatus::COMPLETED
            );

            // Log::info("ProcessRelationships job finished");
        } catch (\Throwable $e) {
            Log::error('Error processing ProcessRelationships job: ' . $e->getMessage(), ['exception' => $e]);

            $this->fail($e);
        }
    }

    /**
     * Handle a job failure.
    
     * @param  \Throwable  $e
     * 
     * @return void
     */
    public function failed(?\Throwable $e): void
    {
        $this->convert->clearData();

        // $errorMessage = $e instanceof \App\Schema\DataTypes\UnsupportedDataTypeException
        //     ? 'The data type of the relational database column is not supported.'
        //     : 'Error: ' . ($e ? $e->getMessage() : 'Unknown error.');

        $errorMessage = $e->getMessage();

        ConversionService::failConvert($this->convert, $this->step, $errorMessage);

        // Трансляція події про помилку при виконанні завдання
        ProcessRelationshipsEvent::dispatch(
            $this->user->id,
            $this->convert->id,
            EventStatus::FAILED
        );//->onQueue('events_emails');
    }

    private function processManyToManyRelations($sqlDatabase, $tables, $collections)
    {
        // $nN = [];

        foreach ($tables as $table) {
            // if ($table->name == "post_tag") continue;
            $foreignKeys = $table->foreignKeys;
            // Тут має бути лише 2 зв'язки ----------
            // Інакше, за логікою Reader-а, це буде complex зв'язок

            if ($foreignKeys->count() > 2) {
                throw new \UnexpectedValueException('Number of Many-to-Many relationships in one table (collection) must be two (2).');
            }

            $first = $foreignKeys->first();
            $second = $foreignKeys->last();

            // $nN[$table->name] = ['link' => [], 'emb' => [], 'hybrid' => []];

            if (
                CircularRef::checkIfExistsByTableName($sqlDatabase->id, $table->name) ||
                CircularRef::checkIfExistsByTableName($sqlDatabase->id, $first->foreign_table) ||
                CircularRef::checkIfExistsByTableName($sqlDatabase->id, $second->foreign_table)
            ) {
                // Save as link with pivot
                ManyToManyLink::createFrom(
                    $table,
                    $first,
                    $second,
                    $collections,
                    MongoManyToManyRelation::LINKING_WITH_PIVOT,
                    false
                );
                // ManyToManyLink::create([
                //     'pivot_collection_id' => $collections->firstWhere('name', $table->name)->id,
                //     'collection1_id' => $collections->firstWhere('name', $first->foreign_table)->id,
                //     'collection2_id' => $collections->firstWhere('name', $second->foreign_table)->id,

                //     'relation_type' => MongoManyToManyRelation::LINKING_WITH_PIVOT,
                //     'is_bidirectional' => false,

                //     'local1_fields' => $first->columns,
                //     'local2_fields' => $second->columns,
                //     'foreign1_fields' => $first->foreign_columns,
                //     'foreign2_fields' =>  $second->foreign_columns,
                // ]);

                // $nN[$table->name]['link'] = ['tables' => [$table->name, $first->foreign_table, $second->foreign_table], 'reason' => 'circular ref'];
                continue;
            }

            $firstTable = Table::where('sql_database_id', $sqlDatabase->id)
                ->where('name', $first->foreign_table)
                ->first();

            $secondTable = Table::where('sql_database_id', $sqlDatabase->id)
                ->where('name', $second->foreign_table)
                ->first();

            if (
                // Якщо у пов'язаних таблиць є зв'язки
                $firstTable->foreignKeys()->exists() ||
                $secondTable->foreignKeys()->exists() ||

                // Або НА них (пов'язані) є зв'язки
                ForeignKey::relationToTableExists($sqlDatabase->id, [$firstTable->name, $secondTable->name], [$first->id, $second->id])
            ) {
                // Save as hybrid
                ManyToManyLink::createFrom(
                    $table,
                    $first,
                    $second,
                    $collections,
                    MongoManyToManyRelation::HYBRID
                );
                continue;

                // $nN[$table->name]['hybrid'] = ['tables' => [$table->name, $firstTable->name, $secondTable->name], 'reason' => 'there are other links'];    
            } else {
                // Save as embedding
                ManyToManyLink::createFrom(
                    $table,
                    $first,
                    $second,
                    $collections,
                    MongoManyToManyRelation::EMBEDDING
                );
                continue;

                // $nN[$table->name]['emb'] = ['tables' => [$table->name, $firstTable->name, $secondTable->name], 'reason' => 'no other links and no circular refs'];
            }
        }

        return true;
        // return $nN;
    }

    private function throwInvalidRelationType($relationType)
    {
        throw new \UnexpectedValueException("Invalid relation type {$relationType}");
    }
}
