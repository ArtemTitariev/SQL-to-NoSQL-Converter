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
use Illuminate\Queue\Middleware\SkipIfBatchCancelled;
use Illuminate\Queue\SerializesModels;
// use Illuminate\Support\Facades\Log;

class ProcessEmbedsJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The job may be attempted only once
     *
     * @var int
     */
    public $tries = 1;

    // protected $sqlConnection;
    // protected $mongoConnection;

    public function __construct(
        public $mainDocumentId,
        public $recordObj,
        public Collection $collection,
        public $sqlDatabase,
        public $mongoDatabase,
    ) {
        //
    }

    public function middleware(): array
    {
        return [new SkipIfBatchCancelled];
    }

    public function handle()
    {
        // Log::info("ProcessEmbedsJob, batch pending jobs = {$this->batch()->pendingJobs}");

        $sqlConnection = ConnectionCreator::create($this->sqlDatabase);
        $mongoConnection = ConnectionCreator::create($this->mongoDatabase); 

        EtlService::processEmbeds(
            $this->mainDocumentId,
            $this->recordObj,
            $this->collection,
            $sqlConnection,
            $mongoConnection
        );
    }
}
