<?php

namespace App\Services\Support;

trait EncryptsIdentifier
{
    public function encryptIdentifier(): string
    {
        return encrypt(json_encode([
            'id' => $this->id,
            'model' => $this::class,
        ]));
    }
}
