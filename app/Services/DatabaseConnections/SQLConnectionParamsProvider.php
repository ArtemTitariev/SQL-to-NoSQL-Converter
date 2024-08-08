<?php

namespace App\Services\DatabaseConnections;

class SQLConnectionParamsProvider {
    
    public function getSupportedDatabases(): array {
        
        return array_map(fn($db) => $db['name'], SUPPORTED_DATABASES);
    }

    public function getCommonConnectionParams(): array {
        return [
            // 'url',
            'host', 
            'port', 
            'database', 
            'username', 
            'password', 
            'charset',
        ];
    }

    public function getSpecificConnectionParams(): array {
        return [
            'mysql' => [
                'collation',
            ],
            'pgsql' => [
                'search_path', 
                'sslmode',
            ],
            // 'mariadb' => [],
            // 'sqlsrv' => [],
        ];
    }
}