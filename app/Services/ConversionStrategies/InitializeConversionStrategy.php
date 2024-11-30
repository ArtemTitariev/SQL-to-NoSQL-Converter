<?php

namespace App\Services\ConversionStrategies;

use App\Models\Convert;
use App\Models\MongoSchema\MongoDatabase;
use App\Models\SQLSchema\SQLDatabase;
use App\Services\DatabaseConnections\ConnectionTester;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class InitializeConversionStrategy implements ConversionStrategyInterface
{
    public function execute(Convert $convert, Request $request, array $extraParams = []): StrategyResult
    {

        $sqlDatabaseParams = $request->validated('sql_database');
        $mongoDatabaseParams = $request->validated('mongo_database');
        $mongoDatabaseParams['driver'] = 'mongodb';

        // Create connection names
        $sqlDatabaseParams['connection_name'] = $extraParams['createConnectionName']->create($sqlDatabaseParams['database']);
        $mongoDatabaseParams['connection_name'] = $extraParams['createConnectionName']->create($mongoDatabaseParams['database']);

        // Test SQL connection
        try {
            ConnectionTester::testSQLConnection($sqlDatabaseParams);
        } catch (\Exception $e) {
            return new StrategyResult (
                result: StrategyResult::STATUSES['FAILED'],
                details: __('SQL database connection error: ') . $e->getMessage(),
            );
        }

        // Test MongoDB connection
        try {
            ConnectionTester::testMongoConnection($mongoDatabaseParams);
        } catch (\Exception $e) {
            return new StrategyResult (
                result: StrategyResult::STATUSES['FAILED'],
                details: __('MongoDB connection error: ') . $e->getMessage(),
            );
            
        }

        // Create database models
        $sqlDatabase = SQLDatabase::create($sqlDatabaseParams);
        $mongoDatabase = MongoDatabase::create($mongoDatabaseParams);

        // Save Convert model
        $convert->fill([
            'user_id' => Auth::id(),
            'sql_database_id' => $sqlDatabase->id,
            'mongo_database_id' => $mongoDatabase->id,
            'description' => $request->validated('description'),
            'status' => Convert::STATUSES['CONFIGURING'],
        ])->save();

        // Return success response
        return new StrategyResult (
            result: StrategyResult::STATUSES['COMPLETED'],
            details: 'The databases connections have been successfully tested. The parameters have been saved.',
            next: config('convert_steps.initialize_conversion.next'),
        );
    }
}
