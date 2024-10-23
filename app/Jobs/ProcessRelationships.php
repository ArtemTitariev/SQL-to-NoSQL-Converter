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
use App\Models\MongoSchema\MongoDatabase;
use App\Models\SQLSchema\CircularRef;
use App\Models\SQLSchema\ForeignKey;
use App\Models\SQLSchema\SQLDatabase;
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

            // Таблиці з many-to-many зв'язками та всі інші
            $manyToManyTables = $this->getManyToManyTables($sqlDatabase, $collections);
            $otherTables = $this->getOtherTables($sqlDatabase, $collections);

            $hasRelations = ! $manyToManyTables->empty();
            $this->processManyToManyRelations($sqlDatabase, $manyToManyTables, $collections);

            foreach ($otherTables as $table) {
                $this->processOtherTableRelations($table, $sqlDatabase, $mongoDatabase, $collections, $hasRelations);
            }

            CompleteProcessRelationshipsStep::execute($this->convert, $this->step, $hasRelations);

            // Трансляція події про успішне завершення
            ProcessRelationshipsEvent::dispatch(
                $this->user->id,
                $this->convert->id,
                EventStatus::COMPLETED
            );
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

        $errorMessage = $e->getMessage();

        ConversionService::failConvert($this->convert, $this->step, $errorMessage);

        // Трансляція події про помилку при виконанні завдання
        ProcessRelationshipsEvent::dispatch(
            $this->user->id,
            $this->convert->id,
            EventStatus::FAILED
        ); //->onQueue('events_emails');
    }

    private function getMongoCollections($mongoDatabase)
    {
        return $mongoDatabase->collections()
            ->with(['linksEmbeddsFrom', 'linksEmbeddsTo', 'manyToManyPivot'])
            ->get();
    }

    private function getManyToManyTables($sqlDatabase, $collections)
    {
        return $sqlDatabase->tables()
            ->whereIn('name', $collections->pluck('name'))
            ->with(['columns', 'foreignKeys'])
            ->whereHas('foreignKeys', function ($query) {
                $query->where('relation_type', RelationType::MANY_TO_MANY);
            })->get();
    }

    private function getOtherTables($sqlDatabase, $collections)
    {
        return $sqlDatabase->tables()
            ->whereIn('name', $collections->pluck('name'))
            ->with(['columns', 'foreignKeys'])
            ->whereDoesntHave('foreignKeys', function ($query) {
                $query->where('relation_type', RelationType::MANY_TO_MANY);
            })
            ->get();
    }

    private function processManyToManyRelations($sqlDatabase, $tables, $collections)
    {
        foreach ($tables as $table) {
            $foreignKeys = $table->foreignKeys;
            // Тут має бути лише 2 зв'язки ----------
            // Інакше, за логікою Reader-а, це буде complex зв'язок

            if ($foreignKeys->count() > 2) {
                throw new \UnexpectedValueException('Number of Many-to-Many relationships in one table (collection) must be two (2).');
            }

            $first = $foreignKeys->first();
            $second = $foreignKeys->last();

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
                ForeignKey::relationToTableExists(
                    $sqlDatabase->id,
                    [$firstTable->name, $secondTable->name],
                    [$first->id, $second->id]
                )
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
            }
        }

        return true;
    }

    private function processOtherTableRelations($table, $sqlDatabase, $mongoDatabase, $collections, bool &$hasRelations)
    {
        $foreignKeys = $table->foreignKeys;

        if ($foreignKeys->isEmpty()) {
            return;
        }

        $hasRelations = true;

        if ($this->isUnexpectedEmbedding($table, $mongoDatabase)) {
            throw new \UnexpectedValueException("Unexpected embedding: {$table->name}");
        }

        foreach ($foreignKeys as $fk) {
            $related = $fk->relatedTable($sqlDatabase->id, true);
            $this->processForeignKeyRelation($table, $fk, $related, $collections, $sqlDatabase, $mongoDatabase);
        }
    }

    private function processForeignKeyRelation($table, $fk, $related, $collections, $sqlDatabase, $mongoDatabase)
    {
        switch ($fk->relation_type) {
            case RelationType::ONE_TO_ONE:
                $this->handleToOneRelation(RelationType::ONE_TO_ONE, $table, $fk, $related, $collections, $sqlDatabase, $mongoDatabase);
                break;
            case RelationType::MANY_TO_ONE:
                $this->handleToOneRelation(RelationType::MANY_TO_ONE, $table, $fk, $related, $collections, $sqlDatabase, $mongoDatabase);
                break;
            case RelationType::SELF_REF:
                LinkEmbedd::createLink($table, $fk, $collections);
                break;
            case RelationType::COMPLEX:
                LinkEmbedd::createLink($table, $fk, $collections);
                break;
                // case RelationType::ONE_TO_MANY:
                // case RelationType::MANY_TO_MANY:
            default:
                throw new \UnexpectedValueException("Invalid relation type {$fk->relation_type->value}");
        }
    }

    private function handleToOneRelation(RelationType $relationType, Table $table, ForeignKey $fk, Table $related, $collections, SQLDatabase $sqlDatabase, MongoDatabase $mongoDatabase): void
    {
        $hasNoForeignKeys = $related->foreignKeys->isEmpty(); // Якщо немає зовнішніх ключів
        $isNotCircular = !CircularRef::checkIfExistsByTableName($sqlDatabase->id, $related->name); // Якщо не в круговому з'єднанні
        $noRelationToOthers = !ForeignKey::relationToTableExists($sqlDatabase->id, [$related->name], [$fk->id]); // Якщо в інших немає посилань на related
        $hasWithinRowLimit = $relationType === RelationType::MANY_TO_ONE ? isWithinRowNumberLimit($related->rows_number) : true; // Якщо записів небагато

        if ($hasNoForeignKeys && $isNotCircular && $noRelationToOthers && $hasWithinRowLimit && !$this->isOtherLinks($related, $mongoDatabase)) {
            // Embedding (be default, embedding to main collection)
            LinkEmbedd::createEmbedding($table, $fk, $collections, true);
        } else {
            // Та, до якої пов'язуються (pk_collection), не має бути вкладеною
            if ($this->isUnexpectedEmbedding($related, $mongoDatabase)) {
                throw new \UnexpectedValueException("Unexpected embedding ({$relationType}): {$table->name}");
            }

            // Linking
            LinkEmbedd::createLink($table, $fk, $collections);
        }
    }

    private function isUnexpectedEmbedding(Table $table, MongoDatabase $mongoDatabase): bool
    {
        return LinkEmbedd::join('collections as pk_collections', 'links_embedds.pk_collection_id', '=', 'pk_collections.id')
            ->where('pk_collections.mongo_database_id', $mongoDatabase->id)
            ->where('pk_collections.name', $table->name)
            ->where('links_embedds.relation_type', MongoRelationType::EMBEDDING)
            ->where('links_embedds.embed_in_main', true)
            ->exists();
    }

    private function isOtherLinks(Table $table, MongoDatabase $mongoDatabase): bool
    {
        return LinkEmbedd::join('collections as fk_collections', 'links_embedds.fk_collection_id', '=', 'fk_collections.id')
            ->join('collections as pk_collections', 'links_embedds.pk_collection_id', '=', 'pk_collections.id')
            ->where(function ($query) use ($mongoDatabase, $table) {
                $query->where('fk_collections.mongo_database_id', $mongoDatabase->id)
                    ->where('fk_collections.name', $table->name)
                    ->where('links_embedds.relation_type', MongoRelationType::LINKING);
            })
            ->orWhere(function ($query) use ($mongoDatabase, $table) {
                $query->where('pk_collections.mongo_database_id', $mongoDatabase->id)
                    ->where('pk_collections.name', $table->name)
                    ->where('links_embedds.relation_type', MongoRelationType::LINKING);
            })
            ->exists();
    }
}
