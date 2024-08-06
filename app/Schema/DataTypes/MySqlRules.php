<?php

namespace App\Schema\DataTypes;

use App\Models\SQLSchema\Column;

class MySqlRules implements RdbDataTypeRulesInterface
{
    use HasColumnNamePattern;

    protected $rules = [
        //bool
        'tinyint(1)' => ['bool', 'int', 'string'],

        //integer numbers
        'bigint unsigned' => ['long', 'string'],
        'bigint' => ['long', 'string'],
        'int' => ['int', 'string'],
        'mediumint' => ['int', 'string'],
        'smallint' => ['int', 'string'],
        'tinyint' => ['int', 'string'],
        
        // fractional numbers
        'decimal' => ['decimal128', 'string'],
        'double' => ['double', 'string'],
        'float' => ['double', 'string'],

        // strings
        'char' => ['string'],
        'varchar' => ['string'],
        'longtext' => ['string'],
        'text' => ['string'],
        'tinytext' => ['string'],

        // dates / times
        'year' => ['string', 'int'],
        'datetime' => ['date', 'string'],
        'date' => ['date', 'string'],
        'time' => ['date', 'string'],
        'timestamp' => ['date', 'string'],
        
        // JSON
        'json' => ['object'],

        // інші
    ];

    public function getSupportedTypes(Column $column): array
    {
        foreach ($this->rules as $pattern => $types) {
            if ($this->matchPattern($pattern, $column->type_name, $column->type)) {
                return $types;
            }
        }
        return [];
    }
}
