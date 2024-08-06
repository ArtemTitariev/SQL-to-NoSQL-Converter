<?php

namespace App\Schema\DataTypes;

use App\Models\SQLSchema\Column;

interface RdbDataTypeRulesInterface
{
    public function getSupportedTypes(Column $column): array;
}