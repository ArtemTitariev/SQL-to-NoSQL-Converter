<?php

namespace App\Schema\DataTypes;

class MySQLRules implements RdbDataTypeRulesInterface
{
    use HasColumnNamePattern;

    protected $rules = [
        // bool
        'tinyint(1)' => ['bool', 'int', 'string'],
        'tinyint(1) unsigned' => ['bool', 'int', 'string'],
        'bool' => ['bool', 'int', 'string'],
        
        // integer numbers
        'bigint unsigned' => ['long', 'string'],
        'bigint' => ['long', 'string'],
        'int unsigned' => ['long', 'string'],
        'int' => ['int', 'long', 'string'],
        'mediumint unsigned' => ['int', 'long', 'string'],
        'mediumint' => ['int', 'long', 'string'],
        'smallint unsigned' => ['int', 'long',  'string'],
        'smallint' => ['int', 'long', 'string'],
        'tinyint unsigned' => ['int', 'long', 'bool', 'string'],
        'tinyint' => ['int', 'long', 'bool', 'string'],

        // fractional numbers
        'decimal unsigned' => ['decimal128', 'string'],
        'decimal' => ['decimal128', 'string'],
        'double unsigned' => ['double', 'long', 'string'],
        'double' => ['double', 'long', 'string'],
        'float unsigned' => ['double', 'long', 'string'],
        'float' => ['double', 'long', 'string'],
        'double precision' => ['double', 'long', 'string'],

        // strings
        'char' => ['string'],
        'varchar' => ['string'],
        'longtext' => ['string'],
        'mediumtext' => ['string'],
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
                ['driver' => 'MySQL', 'dataTypeName' => $typeName, 'dataType' => $type]
            )
        );
    }
}
