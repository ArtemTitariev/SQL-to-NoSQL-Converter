<?php

namespace App\Jobs\Etl;

use App\Models\MongoSchema\Collection;
use App\Services\Etl\EtlService;
use App\Services\DatabaseConnections\ConnectionCreator;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\SkipIfBatchCancelled;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncMainIdsWithMappingJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The job may be attempted only once
     *
     * @var int
     */
    public $tries = 1;

    public function __construct(
        public Collection $pivot,
        public Collection $collection,
        public $foreignFields,
        public $localFields,
        public $sqlDatabase,
        public $mongoDatabase,
    ) {
        //
    }

    public function middleware(): array
    {
        return [new SkipIfBatchCancelled];
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $sqlConnection = ConnectionCreator::create($this->sqlDatabase);
        $mongoConnection = ConnectionCreator::create($this->mongoDatabase);

        EtlService::syncMainIdsWithMapping(
            $this->pivot,
            $this->collection,
            $this->foreignFields,
            $this->localFields,
            $sqlConnection,
            $mongoConnection,
        );
    }
}
