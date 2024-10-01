<?php

namespace App\Enums;

enum RelationType: string
{
    case ONE_TO_ONE = '1-1';
    case ONE_TO_MANY = '1-N';
    case MANY_TO_ONE = 'N-1';
    case MANY_TO_MANY = 'N-N';
    case SELF_REF = 'Self reference';
    case COMPLEX = 'Complex multiple';

    public function isOneToOne(): bool
    {
        return $this === self::ONE_TO_ONE;
    }

    public function isOneToMany(): bool
    {
        return $this === self::ONE_TO_MANY;
    }

    public function isManyToOne(): bool
    {
        return $this === self::MANY_TO_ONE;
    }

    public function isManyToMany(): bool
    {
        return $this === self::MANY_TO_MANY;
    }

    public function isSelfRef(): bool
    {
        return $this === self::SELF_REF;
    }

    public function isComplex(): bool
    {
        return $this === self::COMPLEX;
    }

    public static function getValues(): array {
        return array_map(fn($case) => $case->value, self::cases());
    }
}
