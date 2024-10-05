<?php

namespace App\Enums;

trait EnumValuesTrait {
    public static function getValues(): array {
        return array_map(fn($case) => $case->value, self::cases());
    }
}