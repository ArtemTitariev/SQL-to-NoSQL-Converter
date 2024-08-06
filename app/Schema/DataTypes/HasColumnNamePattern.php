<?php

namespace App\Schema\DataTypes;

trait HasColumnNamePattern {
    
    protected function matchPattern(string $pattern, string $type_name, string $type): bool
    {
        $pattern = str_replace('/', '\/', $pattern);
        return preg_match("/^$pattern(\(.*\))?$/i", $type_name) || preg_match("/^$pattern(\(.*\))?$/i", $type);
    }
}