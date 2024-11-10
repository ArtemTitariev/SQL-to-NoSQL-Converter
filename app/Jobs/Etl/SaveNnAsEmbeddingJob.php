<?php

namespace App\Jobs\Etl;

use App\Jobs\Etl\Handlers\BatchFailureHandler;
use App\Models\MongoSchema\Collection;
use App\Models\MongoSchema\ManyToManyLink;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\SkipIfBatchCancelled;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;

class SaveNnAsEmbeddingJob implements ShouldQueue
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
        public Collection $first,
        public Collection $second,
        public ManyToManyLink $relation,
        public $sqlDatabase,
        public $mongoDatabase,
    ) {
        //
    }

    public function middleware(): array
    {
        return [new SkipIfBatchCancelled];
    }

    public function handle(): void
    {
        // EMBEDDING

        // Bus::batch([
        $this->batch()->add([
            // First
            new EmbedDocumentsForCollectionJob(
                $this->pivot,
                $this->first,
                $this->second,
                $this->relation->local1_fields,
                $this->relation->local2_fields,
                $this->relation->foreign2_fields,
                $this->sqlDatabase,
                $this->mongoDatabase,
            ),
            // Second
            new EmbedDocumentsForCollectionJob(
                $this->pivot,
                $this->second,
                $this->first,
                $this->relation->local2_fields,
                $this->relation->local1_fields,
                $this->relation->foreign1_fields,
                $this->sqlDatabase,
                $this->mongoDatabase,
            ),
        ]);
        // ->catch(new BatchFailureHandler(
        //     "Fail EmbedDocumentsForCollectionJob in SaveNnAsEmbeddingJob for N-N."
        // ))
        // ->onQueue('etl_operations')
        //     ->dispatch();
    }
}
