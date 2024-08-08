<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Supported relational database drivers
    |--------------------------------------------------------------------------
    |
    | Drivers and data type rules class
    |
    */
    'SUPPORTED_DATABASES' => [
        'mysql' => [
            'name' => 'MySQL',
            'rules_class' => App\Schema\DataTypes\MySQLRules::class,
        ],
        'pgsql' => [
            'name' => 'PostgreSQL',
            'rules_class' => App\Schema\DataTypes\PostgreSQLRules::class,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Main relation types in relational databases
    |--------------------------------------------------------------------------
    |
    | Used for schema reader and some models
    |
    */

    'RELATION_TYPES' => [
        'ONE-TO-ONE' => '1-1',
        'ONE-TO-MANY' => '1-N',
        'MANY-TO-MANY' => 'N-N',
        'COMPLEX' => 'Complex multiple',
    ],
];