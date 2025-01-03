<?php

namespace App\Schema\DataTypes;

use InvalidArgumentException;

class RdbDataTypeRulesFactory
{
    public static function create(string $driver): RdbDataTypeRulesInterface
    {
        $drivers = config('constants.SUPPORTED_DATABASES');

        if (isset($drivers[$driver])) {
            $className = $drivers[$driver]['rules_class'];
            
            if (class_exists($className)) {
                return new $className();
            } else {
                throw new InvalidArgumentException("Class $className does not exist");
            }
        }

        throw new InvalidArgumentException(__("Database driver :driver is not supported.", ['driver' => $driver]));
    }
}