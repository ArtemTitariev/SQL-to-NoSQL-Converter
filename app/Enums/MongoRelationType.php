<?php

namespace App\Enums;

enum MongoRelationType: string
{
    case LINKING = 'Linking';
    case EMBEDDING = 'Embedding';

    use EnumValuesTrait;
    
    public function isLinking(): bool
    {
        return $this === self::LINKING;
    }

    public function isEmbedding(): bool
    {
        return $this === self::EMBEDDING;
    }
}
