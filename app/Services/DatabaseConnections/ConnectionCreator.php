<?php

namespace App\Services\DatabaseConnections;

use App\Models\MongoSchema\MongoDatabase;
use App\Models\SQLSchema\SQLDatabase;
use Illuminate\Support\Facades\DB;

class ConnectionCreator
{
    /**
     * Create new database connection
     * 
     * @param string $name connection name
     * 
     * @param array | App\Models\MongoSchema\MongoDatabase | App\Models\SQLSchema\SQLDatabase $params
     * 
     * @throws InvalidArgumentException
     * @throws PDOException
     * 
     * @return \Illuminate\Database\Connection
     */
    public static function create(
        string $name,
        array | MongoDatabase | SQLDatabase &$params
    ) {
        if (is_object($params)) {
            $params = $params->toArray();
        }

        config(["database.connections.$name" => $params]);

        $connection = DB::connection("$name");

        return $connection;
    }
}
