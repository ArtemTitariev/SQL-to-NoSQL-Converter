<?php

namespace App\Services\DatabaseConnections;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Schema;
use MongoDB\Exception\RuntimeException as MongoDBException;

class ConnectionTester
{
    /**
     * Create and test SQL connection
     * 
     * @param array $params array of params
     * 
     * @throws PDOExpection
     * 
     * @return \Illuminate\Database\Connection
     */
    public static function testSQLConnection(array &$params): bool {
        $connection = ConnectionCreator::create($params);
        
        // Get PDO
        $connection->getPdo();
        // Check access to schema
        $builder = Schema::connection($params['connection_name']);
        $builder->getTables();

        return true;
    }

     /**
     * Create and test MongoDB connection
     * 
     * @param array $params array of params
     * 
     * @throws PDOExpection
     * @throws MongoDB\Driver\Exception\Exception
     * 
     * @return \Illuminate\Database\Connection
     */
    public static function testMongoConnection(array &$params): bool {
        $connection = ConnectionCreator::create($params);

        // Get client
        $client = $connection->getMongoClient();

        // Check connection
        $databases = $client->listDatabases();
        
        $databaseName = $params['database'];
        // Check if database exists
        $databaseExists = collect(iterator_to_array($databases))->contains(function ($database) use ($databaseName) {
            return $database->getName() === $databaseName;
        });
        
        if (!$databaseExists) {
            throw new MongoDBException(__("Connected successfully to MongoDB, but the database does not exist."));
        }
    
        return true;
    }
}
