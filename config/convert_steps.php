<?php

return [

    'initialize_conversion' => [ //форма для параметрів з'єднань, тестування з'єднань, створення конвертування
        'number' => 1,
        'key' => 'initialize_conversion',
        'name' => 'Create and test databases connections',
        'view' => 'convert.create',
        'next' => 'read_schema',
        'is_manual' => false
    ],

    'read_schema' => [ // читання й аналіз схеми
        'number' => 2,
        'key' => 'read_schema',
        'name' => 'Analyzing the relational database schema',
        'next' => 'adjust_datatypes',
        'is_manual' => false
        // 'is_manual' => true,
        // 'view' => 'convert.read_schema-loading'
    ],

    'adjust_datatypes' => [ // вибір типів даних, збереження схеми MongoDB
        'number' => 3,
        'key' => 'adjust_datatypes',
        'name' => 'Adjusting data types',
        'view' => 'convert.adjust_datatypes',
        'next' => 'adjust_relationships',
        'is_manual' => true
    ],

    'adjust_relationships' => [ //вибір зв'язків (AJAX??), валідація,
        'number' => 4,
        'key' => 'adjust_relationships',
        'name' => 'Adjusting relationships',
        'view' => 'convert.adjust_relationships',
        'next' => 'etl',
        'is_manual' => true
    ],

    // тут ще можна додати крок (або два) для створення індексів + валідаторів для колекцій

    'etl' => [ //ETL + mb send email??
        'number' => 5,
        'key' => 'etl',
        'name' => 'Extract-transform-load operations',
        'next' => 'finalize_conversion',
        'is_manual' => false
    ],

    'finalize_conversion' => [ //send email ?????
        'number' => 6,
        'next' => null,
        'is_manual' => false
    ],

];
