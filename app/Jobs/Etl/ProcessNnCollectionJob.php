<?php

namespace App\Jobs\Etl;

use App\Enums\MongoManyToManyRelation;
use App\Models\MongoSchema\Collection;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\SkipIfBatchCancelled;
use Illuminate\Queue\SerializesModels;
// use Illuminate\Support\Facades\Log;

class ProcessNnCollectionJob implements ShouldQueue
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
    ) {
        // 
    }

    public function middleware(): array
    {
        return [new SkipIfBatchCancelled];
    }

    public function handle(): void
    {
        $relation = $this->collection->manyToManyPivot()->first();

        $first = $relation->collection1()->with(['fields', 'linksEmbeddsFrom', 'linksEmbeddsTo'])->first();
        $second = $relation->collection2()->with(['fields', 'linksEmbeddsFrom', 'linksEmbeddsTo'])->first();

        // Bus::batch([
        $this->batch()->add([
            // 1. First
            new ProcessCollectionJob($first, $this->sqlDatabase, $this->mongoDatabase, $first->hasEmbedds(), $relation->foreign1_fields),
            // 2. Second
            new ProcessCollectionJob($second, $this->sqlDatabase, $this->mongoDatabase, $second->hasEmbedds(), $relation->foreign2_fields),
            // Pivot
            match ($relation->relation_type) {
                MongoManyToManyRelation::LINKING_WITH_PIVOT => new SaveNnAsLinkWithPivotJob(
                    $this->collection,
                    $first,
                    $second,
                    $relation,
                    $this->sqlDatabase,
                    $this->mongoDatabase,
                ),
                MongoManyToManyRelation::EMBEDDING => new SaveNnAsEmbeddingJob(
                    $this->collection,
                    $first,
                    $second,
                    $relation,
                    $this->sqlDatabase,
                    $this->mongoDatabase,
                ),
                MongoManyToManyRelation::HYBRID => new SaveNnAsHybridJob(
                    $this->collection,
                    $first,
                    $second,
                    $relation,
                    $this->sqlDatabase,
                    $this->mongoDatabase,
                ),
                default => null,
            },
        ]);
        // ->catch(new BatchFailureHandler(
        //     "Fail N-N processing in ProcessNnCollectionJob for collection: {$this->collection->name}."
        // ))
        //     ->onQueue('etl_operations')
        //     ->dispatch();
    }
}
