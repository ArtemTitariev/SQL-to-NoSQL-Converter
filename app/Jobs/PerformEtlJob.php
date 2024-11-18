<?php

namespace App\Jobs;

use App\Actions\CompleteConvert;
use App\Actions\FailConvert;
use App\Enums\MongoRelationType;
use App\Jobs\Etl\ClearJob;
use App\Jobs\Etl\ProcessCollectionJob;
use App\Jobs\Etl\ProcessNnCollectionJob;
use App\Models\Convert;
use Illuminate\Bus\Batch;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class PerformEtlJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        protected Convert $convert,
    ) {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $this->convert->setStatusAsInProgress();

        $sqlDatabase = $this->convert->sqlDatabase;
        $mongoDatabase = $this->convert->mongoDatabase;

        $this->processOrdinaryCollections($this->convert, $sqlDatabase, $mongoDatabase);
    }

    protected function processOrdinaryCollections($convert, $sqlDatabase, $mongoDatabase)
    {
        $collections = $mongoDatabase->collections()->with(['fields', 'linksEmbeddsFrom', 'manyToManyPivot'])
            ->whereDoesntHave('manyToManyPivot')
            ->whereDoesntHave('manyToManyFirst')
            ->whereDoesntHave('manyToManySecond')
            ->whereDoesntHave('linksEmbeddsTo', function ($subquery) {
                $subquery->where('relation_type', MongoRelationType::EMBEDDING)
                    ->where('embed_in_main', true);
            })->whereDoesntHave('linksEmbeddsFrom', function ($subquery) {
                $subquery->where('relation_type', MongoRelationType::EMBEDDING)
                    ->where('embed_in_main', false);
            })
            ->orderBy('name')
            ->get();

        if ($collections->isEmpty()) {
            $this->processNnCollections($convert, $sqlDatabase, $mongoDatabase);
            return;
        }

        $this->processOrdinaryCollectionsInBatch($collections, $sqlDatabase, $mongoDatabase, $convert);
    }

    protected function processOrdinaryCollectionsInBatch($collections, $sqlDatabase, $mongoDatabase, Convert $convert)
    {
        $batch = Bus::batch([]);

        foreach ($collections as $collection) {
            $batch->add(new ProcessCollectionJob($collection, $sqlDatabase, $mongoDatabase, $collection->hasEmbedds()));
        }

        $batch->then(function (Batch $batch) use ($convert, $sqlDatabase, $mongoDatabase) {
            $this->processNnCollections($convert, $sqlDatabase, $mongoDatabase);
        })
            ->catch(function (Batch $batch, \Throwable $e) use ($convert) {
                Log::error("Batch failed for ordinary collections", [
                    'exception' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                    'batch_id' => $batch->id,
                ]);
                $batch->cancel();
                ClearJob::dispatch($convert)->onQueue('etl_operations');
                FailConvert::execute($convert);
            })
            ->onQueue('etl_operations')
            ->dispatch();
    }

    protected function processNnCollections($convert, $sqlDatabase, $mongoDatabase)
    {
        $collections = $mongoDatabase->collections()
            ->with(['fields', 'linksEmbeddsFrom', 'linksEmbeddsTo', 'manyToManyPivot'])
            ->whereHas('manyToManyPivot')
            // ->whereDoesntHave('linksEmbeddsTo')
            ->whereDoesntHave('manyToManyFirst')
            ->whereDoesntHave('manyToManySecond')
            ->orderBy('name')
            ->get();

        if ($collections->isEmpty()) {
            ClearJob::dispatch($convert)->onQueue('etl_operations');
            CompleteConvert::execute($convert);

            return;
        }

        $this->processNnCollectionsInBatch($collections, $sqlDatabase, $mongoDatabase, $convert);
    }

    protected function processNnCollectionsInBatch($collections, $sqlDatabase, $mongoDatabase, Convert $convert)
    {
        $batch = Bus::batch([]);

        foreach ($collections as $collection) {
            $batch->add(new ProcessNnCollectionJob($collection, $sqlDatabase, $mongoDatabase, $collection->hasEmbedds()));
        }

        $batch->then(function (Batch $batch) use ($convert) {
            CompleteConvert::execute($convert);
        })
            ->catch(function (Batch $batch, \Throwable $e) use ($convert) {
                Log::error("Batch failed for many-to-many collections", [
                    'exception' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                    'batch_id' => $batch->id,
                ]);
                $batch->cancel();
                FailConvert::execute($convert);
            })
            ->finally(function (Batch $batch) use ($convert) {
                ClearJob::dispatch($convert)->onQueue('etl_operations');
            })
            ->onQueue('etl_operations')
            ->dispatch();
    }
}
