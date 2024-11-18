<?php

namespace App\Services\ConversionStrategies;

use App\Models\Convert;
// use App\Models\SQLSchema\SQLDatabase;
// use App\Schema\SQL\Mapper;
// use App\Schema\SQL\Reader;
// use App\Services\DatabaseConnections\ConnectionCreator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

use App\Jobs\ReadSchema as ReadSchemaJob;

class ReadSchemaStrategy implements ConversionStrategyInterface
{
    public function execute(Convert $convert, Request $request, array $extraParams = []): StrategyResult
    {
        ReadSchemaJob::dispatch(
            Auth::user(),
            $convert,
            config('convert_steps.read_schema.key')
        )->onQueue('read_schema')
        ->delay(now()->addSeconds(2)); /////////////////////

        return new StrategyResult (
            result: StrategyResult::STATUSES['PROCESSING'],
            details: 'Relational database schema analysis continues.',
            route: config('convert_steps.read_schema.route'),
        );
    }
}
