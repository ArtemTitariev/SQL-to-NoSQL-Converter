<?php

namespace App\Enums;

trait EnumValuesTrait {
    public static function getValues(): array {
        return array_map(fn($case) => $case->value, self::cases());
    }
    
    public static function getKeys(): array {
        return array_map(fn($case) => $case->name, self::cases());
    }

    public static function toArray(): array {
        return array_map(fn($case) => ['key' => $case->name, 'value' => $case->value], self::cases());
    }
}