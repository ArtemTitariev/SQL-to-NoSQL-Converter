<?php

namespace App\Enums;

enum MongoManyToManyRelation: string
{
    case LINKING_WITH_PIVOT = 'Linking with pivot';
    case EMBEDDING = 'Embedding';
    case HYBRID = 'Hybrid';

    use EnumValuesTrait;
    
    public function isLinkingWithPivot(): bool
    {
        return $this === self::LINKING_WITH_PIVOT;
    }

    public function isEmbedding(): bool
    {
        return $this === self::EMBEDDING;
    }

    public function isHybrid(): bool
    {
        return $this === self::HYBRID;
    }

    // public static function getValues(): array {
    //     return array_map(fn($case) => $case->value, self::cases());
    // }
}