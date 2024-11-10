<?php

namespace App\Jobs\Etl;

use App\Models\Convert;
use App\Services\Etl\EtlService;
// use App\Models\IdMapping;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class ClearJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    // /**
    //  * The job may be attempted only once
    //  *
    //  * @var int
    //  */
    // public $tries = 1;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Convert $convert,
    ) {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        DB::table('id_mappings')
            ->leftJoin('tables', 'id_mappings.table_id', '=', 'tables.id')
            ->leftJoin('collections', 'id_mappings.collection_id', '=', 'collections.id')
            ->where('tables.sql_database_id', $this->convert->sql_database_id)
            ->where('collections.mongo_database_id', $this->convert->mongo_database_id)
            ->delete();
    }
}
