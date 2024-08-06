<?php

namespace App\Schema\DataTypes;

use InvalidArgumentException;

class RdbDataTypeRulesFactory
{
    public static function create(string $driver): RdbDataTypeRulesInterface
    {
        switch ($driver) {
            case 'mysql':
                return new MySqlRules();
            case 'pgsql':
                return new PgSqlRules();
            
            default:
                throw new InvalidArgumentException("Unsupported database driver: $driver");
        }
    }
}