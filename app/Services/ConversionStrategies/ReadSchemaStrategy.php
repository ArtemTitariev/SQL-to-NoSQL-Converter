<?php

namespace App\Services\ConversionStrategies;

use App\Models\Convert;
use App\Models\SQLSchema\SQLDatabase;
use App\Schema\SQL\Reader;
use App\Schema\SQL\Mapper;
use App\Services\DatabaseConnections\ConnectionCreator;
use Illuminate\Http\Request;

class ReadSchemaStrategy implements ConversionStrategyInterface
{
    public function execute(Convert $convert, Request $request, array $extraParams = [])
    {
        $sqlDatabase = $convert->sqlDatabase;
    try {
        $connection = ConnectionCreator::create($sqlDatabase);
        
        $reader = new Reader($connection->getSchemaBuilder());
        $mapper = new Mapper($sqlDatabase, $reader);

        $mapper->mapSchema($sqlDatabase);
    } catch (\Exception $e) {
        $convert->clearData();

        // return [
        //     'status' => 'failed',
        //     'error' => $e->getMessage(),
        // ];
        throw $e;
    }

        // Return success response
        return [
            'status' => 'success',
            'details' => 'Relational database schema has been analyzed.',
            'next' => config('convert_steps.read_schema.next'),
        ];
    }
}
