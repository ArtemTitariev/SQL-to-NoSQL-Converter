<?php

namespace App\Schema\DataTypes;

use function PHPSTORM_META\type;

trait HasColumnNamePattern {
    
    protected function matchPattern(string $pattern, string $typeName, string $type): bool
    {
        $pattern = str_replace('/', '\/', $pattern);
        return $pattern === $type || preg_match("/^$pattern(\(.*\))?$/i", $type) || preg_match("/^$pattern(\(.*\))?$/i", $typeName);
    }
}