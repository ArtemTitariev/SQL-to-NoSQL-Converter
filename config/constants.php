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

    // /*
    // |--------------------------------------------------------------------------
    // | Main relation types in relational databases
    // |--------------------------------------------------------------------------
    // |
    // | Used for schema reader and some models
    // |
    // */
    'RELATION_TYPES' => [
        'ONE-TO-ONE' => '1-1',
        'ONE-TO-MANY' => '1-N',
        'MANY-TO-MANY' => 'N-N',
        'SELF-REF' => 'Self reference',
        'COMPLEX' => 'Complex multiple',
    ],

    /*
    |--------------------------------------------------------------------------
    | Analyzing the relational database schema
    |--------------------------------------------------------------------------
    |
    | When analyzing a relational database schema, the number of records in 
    | each table is calculated. Since there can be an extremely large 
    | number of rows, it is important to know only a small number of records.
    | The first key indicates the maximum exact number of rows.
    | If the number of rows in the table is greater, 
    | the value defined by the second key is used.
    |
    */
    'MAX_ROWS_LIMIT' => 500,
    'MAX_ROWS_LIMIT_EXCEEDED' => -1,

    'MAX_COLLECTION_EMBEDDED_FIELDS' => 100,

    'MAX_EMBEDDING_DEPTH' => 5,
];