<?php

namespace App\Schema\DataTypes;

use App\Models\SQLSchema\Column;

class PgSqlRules implements RdbDataTypeRulesInterface
{
    use HasColumnNamePattern;
    
    protected $rules = [
        //bool
        'boolean' => ['bool', 'string'],

        //integer numbers
        'bigint' => ['long', 'string'],
        'integer' => ['int', 'string'],
        'smallint' => ['int', 'string'],
        
        // fractional numbers
        'numeric' => ['decimal128', 'string'],
        'double precision' => ['double', 'string'],

        // strings
        'character' => ['string'],
        'character varying' => ['string'],
        'text' => ['string'],

        // dates / times
        'timestamp(0) with time zone' => ['date', 'string'],
        'timestamp(0) without time zone' => ['date', 'string'],
        'time(0) with time zone' => ['date', 'string'],
        'time(0) without time zone' => ['date', 'string'],
        
        // networking
        'inet' => ['string'],
        'cidr' => ['string'],
        'macaddr' => ['string'],
        'macaddr8' => ['string'],

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