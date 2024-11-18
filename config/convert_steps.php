<?php

return [

    'initialize_conversion' => [ // форма для параметрів з'єднань, тестування з'єднань, створення конвертування
        'number' => 1,
        'key' => 'initialize_conversion',
        'name' => 'Create and test databases connections',
        'view' => 'convert.create',
        'next' => 'read_schema',
        'is_manual' => false,
    ],

    'read_schema' => [ // Читання й аналіз схеми
        'number' => 2,
        'key' => 'read_schema',
        'name' => 'Analyzing the relational database schema',
        'next' => 'adjust_datatypes',
        'is_manual' => false,
        'route' => 'convert.process_read_schema'
    ],

    'adjust_datatypes' => [ // Вибір типів даних, збереження схеми MongoDB
        'number' => 3,
        'key' => 'adjust_datatypes',
        'name' => 'Adjusting data types',
        'view' => 'convert.adjust_datatypes',
        'next' => 'process_relationships',
        'is_manual' => true,
    ],

    'process_relationships' => [ // Попередній аналіз зв'язків, пропозиція варіанту їх організації
        // Якщо зв'язків немає, наступний крок (adjust_relationships) пропускається
        'number' => 4,
        'key' => 'process_relationships',
        'name' => 'Processing relationships',
        'next' => 'adjust_relationships',
        'is_manual' => false,
        'route' => 'convert.process_relationships',
    ],

    'adjust_relationships' => [ //вибір зв'язків, валідація,
        'number' => 5,
        'key' => 'adjust_relationships',
        'name' => 'Adjusting relationships',
        'view' => 'convert.adjust_relationships',
        'next' => 'etl',
        'is_manual' => true,
    ],

    // тут ще можна додати крок (або два) для створення індексів + валідаторів для колекцій

    'etl' => [ //ETL + send email
        'number' => 6,
        'key' => 'etl',
        'name' => 'Extract-transform-load operations',
        'next' => null,
        'is_manual' => false,
        'route' => 'convert.process_etl',
    ],

];
