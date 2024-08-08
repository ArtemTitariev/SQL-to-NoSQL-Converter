<?php

namespace App\Schema\DataTypes;

use InvalidArgumentException;

class RdbDataTypeRulesFactory
{
    public static function create(string $driver): RdbDataTypeRulesInterface
    {
        $drivers = SUPPORTED_DATABASES;

        if (isset($drivers[$driver])) {
            $className = $drivers[$driver]['rules_class'];
            
            if (class_exists($className)) {
                return new $className();
            } else {
                throw new InvalidArgumentException("Class $className does not exist");
            }
        }

        throw new InvalidArgumentException("Unsupported database driver: $driver");
    }
}