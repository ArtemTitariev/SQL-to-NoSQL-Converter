<?php

namespace App\Schema\DataTypes;

use App\Models\SQLSchema\Column;

class MySQLRules implements RdbDataTypeRulesInterface
{
    use HasColumnNamePattern;

    protected $rules = [
        //bool
        'tinyint(1)' => ['bool', 'int', 'string'],
        'tinyint(1) unsigned' => ['bool', 'int', 'string'],
        'bool' => ['bool', 'int', 'string'],
        
        //integer numbers
        'bigint unsigned' => ['long', 'string'],
        'bigint' => ['long', 'string'],
        'int unsigned' => ['long', 'string'],
        'int' => ['int', 'string'],
        'mediumint unsigned' => ['int', 'string'],
        'mediumint' => ['int', 'string'],
        'smallint unsigned' => ['int', 'string'],
        'smallint' => ['int', 'string'],
        'tinyint unsigned' => ['int', 'string'],
        'tinyint' => ['int', 'string'],

        // fractional numbers
        'decimal unsigned' => ['decimal128', 'string'],
        'decimal' => ['decimal128', 'string'],
        'double unsigned' => ['double', 'string'],
        'double' => ['double', 'string'],
        'float unsigned' => ['double', 'string'],
        'float' => ['double', 'string'],
        'double precision' => ['double', 'string'],

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
