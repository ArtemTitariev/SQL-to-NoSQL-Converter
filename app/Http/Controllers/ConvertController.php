<?php

namespace App\Http\Controllers;

use App\Actions\CreateConnectionName;
use App\Http\Requests\StoreConvertRequest;
use App\Models\Convert;
use App\Models\MongoSchema\MongoDatabase;
use App\Models\SQLSchema\SQLDatabase;
use App\Services\DatabaseConnections\SQLConnectionParamsProvider;
use App\Services\DatabaseConnections\ConnectionCreator;
use App\Services\DatabaseConnections\ConnectionTester;
use Illuminate\Http\Request;

class ConvertController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $converts = Convert::with('user', 'sqlDatabase', 'mongoDatabase')->get();

        return view('convert.index', compact('converts'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(SQLConnectionParamsProvider $paramsProvider)
    {
        $supportedDatabases = $paramsProvider->getSupportedDatabases();
        $commonFields = $paramsProvider->getCommonConnectionParams();
        $dbSpecificFields = $paramsProvider->getSpecificConnectionParams();

        return view('convert.create', compact('supportedDatabases', 'commonFields', 'dbSpecificFields'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(
        StoreConvertRequest $request,
        CreateConnectionName $createConnectionName
    ) {

        $sqlDatabaseParams = $request->validated('sql_database');
        $mongoDatabaseParams = $request->validated('mongo_database');
        $mongoDatabaseParams['driver'] = 'mongodb';

        // create connection names
        $sqlDatabaseParams['connection_name'] = $createConnectionName->create($sqlDatabaseParams['database']);
        $mongoDatabaseParams['connection_name'] = $createConnectionName->create($mongoDatabaseParams['database']);

        try {
            ConnectionTester::testSQLConnection($sqlDatabaseParams);
            ConnectionTester::testMongoConnection($mongoDatabaseParams);
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->withErrors($e->getMessage());
            // dd($e->getMessage());
        }

        // create database models
        $sqlDatabase = SQLDatabase::create($sqlDatabaseParams);
        $mongoDatabase = MongoDatabase::create($mongoDatabaseParams);

        // Create Convert model
        Convert::create([
            'user_id' => auth()->id(),
            'sql_database_id' => $sqlDatabase->id,
            'mongo_database_id' => $mongoDatabase->id,
            'description' => $request->validated('description'),
            'status' => Convert::STATUSES['IN_PROGRESS'],
        ]);

        return redirect()->route('converts.index');
        // ->with('status', 'Conversion created.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        return 'show page';
    }

    // /**
    //  * Show the form for editing the specified resource.
    //  */
    // public function edit(string $id)
    // {
    //     // 
    // }

    // /**
    //  * Update the specified resource in storage.
    //  */
    // public function update(Request $request, string $id)
    // {
    //     //
    // }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
