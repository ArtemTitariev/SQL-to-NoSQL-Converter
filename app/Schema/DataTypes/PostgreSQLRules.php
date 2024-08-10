<?php

namespace App\Schema\DataTypes;

use App\Models\SQLSchema\Column;

class PostgreSQLRules implements RdbDataTypeRulesInterface
{
    use HasColumnNamePattern;
    
    protected $rules = [
        //bool
        'boolean' => ['bool', 'string'],

        //integer numbers
        'bigint' => ['long', 'string'],
        'integer' => ['int', 'string'],
        'smallint' => ['int', 'string'],

        'bigserial' => ['int', 'string'],
        'serial' => ['int', 'string'],
        'smallserial' => ['int', 'string'],
        
        // fractional numbers
        'decimal' => ['decimal128', 'string'],
        'numeric' => ['decimal128', 'string'],
        'real' => ['double', 'string'],
        'double precision' => ['double', 'string'],

        // strings
        'character' => ['string'],
        'character varying' => ['string'],
        'bpchar' => ['string'],
        'text' => ['string'],

        // dates / times
        'timestamp(0) with time zone' => ['date', 'string'],
        'timestamp(0) without time zone' => ['date', 'string'],
        'time(0) with time zone' => ['date', 'string'],
        'time(0) without time zone' => ['date', 'string'],
        'date' => ['date', 'string'],

        // networking
        'inet' => ['string'],
        'cidr' => ['string'],
        'macaddr' => ['string'],
        'macaddr8' => ['string'],

        // JSON
        'json' => ['object'],

        // інші
    ];

    public function getSupportedTypes(string $typeName, string $type): array
    {
        foreach ($this->rules as $pattern => $types) {
            if ($this->matchPattern($pattern, $typeName, $type)) {
                return $types;
            } else {
                throw new UnsupportedDataTypeException(
                    $type,
                    __(
                        ":driver :dataType data type is not supported.",
                        ['driver' => 'PostgreSQL', 'dataType' => $typeName]
                    )
                );
            }
        }
    }
}