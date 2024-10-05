<?php

namespace App\Enums;

enum EventStatus: string
{
    case COMPLETED = 'completed';
    case FAILED = 'failed';

    use EnumValuesTrait;
    
    public function isCompleted(): bool
    {
        return $this === self::COMPLETED;
    }

    public function isFailed(): bool
    {
        return $this === self::FAILED;
    }
}