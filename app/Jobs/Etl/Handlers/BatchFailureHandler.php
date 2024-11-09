<?php

namespace App\Jobs\Etl\Handlers;

use Illuminate\Bus\Batch;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class BatchFailureHandler
{
    use SerializesModels;

    protected $collection;

    public function __construct($collection)
    {
        $this->collection = $collection;
    }

    public function __invoke(Batch $batch, Throwable $e)
    {
        Log::error("Batch failed for embeds in collection {$this->collection->name}: " . $e->getMessage());
        throw $e;
    }
}
