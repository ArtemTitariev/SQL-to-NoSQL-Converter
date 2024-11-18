<?php

namespace App\Services\DatabaseConnections;

use Illuminate\Support\Facades\Schema;
// use MongoDB\Exception\RuntimeException as MongoDBException;

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

        $db = $client->selectDatabase($params['database']);
        $collectionName = 'test';

        // Find collection name, that doesn't exist in the database 
        while (!empty(iterator_to_array($db->listCollections(['filter' => ['name' => $collectionName]])))) {
            $collectionName .= rand(1, 1000);
        }

        // Create collection and insert a document
        $collection = $db->$collectionName;
        $collection->insertOne(['name' => 'testDocument']);

        // Create an index
        $collection->createIndex(['name' => 1]);

         // Delete the collection
         $db->dropCollection($collectionName);

        return true;
    }
}
