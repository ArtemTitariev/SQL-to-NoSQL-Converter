<?php

namespace App\Schema\DataTypes;

interface RdbDataTypeRulesInterface
{
    public function getSupportedTypes(string $typeName, string $type): array;
}