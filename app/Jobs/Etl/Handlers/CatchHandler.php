<?php

namespace App\Jobs\Etl\Handlers;

use App\Jobs\Etl\ClearJob;
use Illuminate\Bus\Batch;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CatchHandler
{
    use SerializesModels;

    protected $convert;
    protected $collection;
    protected $index;

    public function __construct($convert, $collection, $index)
    {
        $this->convert = $convert;
        $this->collection = $collection;
        $this->index = $index;
    }

    public function handle(Batch $batch, \Throwable $e)
    {
        ClearJob::dispatch($this->convert);
        Log::error("--Batch failed for collection {$this->collection->name} (index {$this->index}): " . $e->getMessage());
    }
}