<?php

return [

    'initialize_conversion' => [ //форма для параметрів з'єднань, тестування з'єднань, створення конвертування
        'number' => 1,
        'name' => 'Create and test databases connections',
        'view' => 'convert.create',
        'next' => 'read_schema',
        'is_manual' => false
    ],

    'read_schema' => [ // читання й аналіз схеми
        'number' => 2,
        'name' => 'Analyzing the relational database schema',
        'next' => 'select_datatypes',
        'is_manual' => false
    ],

    'adjust_datatypes' => [ // вибір типів даних, збереження схеми MongoDB
        'number' => 3,
        'name' => 'Adjusting data types',
        'view' => 'convert.step3',
        'next' => 'select_relationships',
        'is_manual' => true
    ],

    'select_relationships' => [ //вибір зв'язків (AJAX??), валідація,
        'number' => 4,
        'name' => 'Selecting relationships',
        'view' => 'convert.step4',
        'next' => 'etl',
        'is_manual' => true
    ],

    // тут ще можна додати крок (або два) для створення індексів + валідаторів для колекцій

    'etl' => [ //ETL + mb send email??
        'number' => 5,
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
