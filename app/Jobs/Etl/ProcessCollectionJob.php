<?php

namespace App\Jobs\Etl;

use App\Models\MongoSchema\Collection;
use App\Services\DatabaseConnections\ConnectionCreator;
use App\Services\Etl\EtlService;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\Middleware\SkipIfBatchCancelled;
// use Illuminate\Support\Facades\Log;

class ProcessCollectionJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The job may be attempted only once
     *
     * @var int
     */
    public $tries = 1;

    public function __construct(
        public Collection $collection,
        public $sqlDatabase,
        public $mongoDatabase,
        public bool $hasEmbedds,
        public ?array $identificationColumns = null,
    ) {
        // 
    }

    public function middleware(): array
    {
        return [new SkipIfBatchCancelled];
    }

    public function handle(): void
    {
        $sqlConnection = ConnectionCreator::create($this->sqlDatabase);
        $mongoConnection = ConnectionCreator::create($this->mongoDatabase);

        $mongoConnection->dropCollection($this->collection->name);

        $table = $this->collection->sqlTable;

        // Батч для завдань ProcessEmbedsJob, які є частиною поточного ProcessCollectionJob
        $embedJobs = [];

        $sqlConnection->table($table->name)
            ->orderBy($table->getOrderingColumnName())
            ->lazy()
            ->each(function (object $recordObj) use ($table, &$embedJobs, $mongoConnection) {
                $record = EtlService::processDocument($recordObj, $this->collection);
                $mainDocumentId = EtlService::writeToMongo($mongoConnection, $this->collection, $record);
                if ($this->hasEmbedds) {
                    $embedJobs[] = new ProcessEmbedsJob(
                        $mainDocumentId,
                        $recordObj,
                        $this->collection,
                        $this->sqlDatabase,
                        $this->mongoDatabase,
                    );
                }

                EtlService::createIdMapping(
                    $table,
                    $this->collection,
                    $mainDocumentId,
                    $recordObj,
                    $this->identificationColumns,
                );
            });

            $this->batch()->add($embedJobs);

        // Bus::batch($embedJobs)
        //     ->catch(new BatchFailureHandler(
        //         "Fail ProcessEmbedsJob in ProcessCollectionJob for collection: {$this->collection->name}."
        //     ))
        //     ->onQueue('etl_operations')
        //     ->dispatch();
    }
}
