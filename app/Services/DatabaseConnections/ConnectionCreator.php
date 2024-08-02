<?php

namespace App\Services\DatabaseConnections;

use Illuminate\Support\Facades\DB;

class MySQLConnectionCreator {

    /**
     * Creates new connection
     * 
     * @param string $name connection name
     * 
     * @param array $params array of params
     * 
     * @return \Illuminate\Database\Connection
     */
    public static function create(string $name, array $params) {

        config(["database.connections.$name" => $params]);

        $connection = DB::connection("$name");

        return $connection;
    }
}