<?php

namespace App\Schema\DataTypes;

class PostgreSQLRules implements RdbDataTypeRulesInterface
{
    use HasColumnNamePattern;

    protected $rules = [
        //bool
        'boolean' => ['bool', 'string'],

        // integer numbers
        'bigint' => ['long', 'string'],
        'integer' => ['int', 'long', 'string'],
        'smallint' => ['int', 'long', 'string'],

        'bigserial' => ['int', 'long', 'string'],
        'serial' => ['int', 'long', 'string'],
        'smallserial' => ['int', 'long', 'string'],

        // fractional numbers
        'decimal' => ['decimal128', 'string'],
        'numeric' => ['decimal128', 'string'],
        'real' => ['double', 'long', 'string'],
        'double precision' => ['double', 'long', 'string'],

        // strings
        'character' => ['string'],
        'char' => ['string'],
        'character varying' => ['string'],
        'varchar' => ['string'],
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
            }
        }
        
        throw new UnsupportedDataTypeException(
            $type,
            __(
                "messages.unsupported_data_type",
                ['driver' => 'PostgreSQL', 'dataTypeName' => $typeName, 'dataType' => $type]
            )
        );
    }
}
