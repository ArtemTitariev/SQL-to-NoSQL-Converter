<?php

namespace App\Actions;

use App\Models\ConversionProgress;
use App\Models\Convert;
use InvalidArgumentException;

class SaveConversionProgress
{

    protected ConversionProgress $progress;

    public function __construct(ConversionProgress $progress)
    {
        $this->progress = $progress;
    }

    public function save(Convert $convert, int $step, string $status, string $details)
    {
        if (! in_array($status, ConversionProgress::STATUSES)) {
            throw new InvalidArgumentException("Invalid status: $status");
        }

        ConversionProgress::updateOrCreate(
            [
                'convert_id' => $convert->id,
                'step' => $step
            ],
            [
                'status' => $status,
                'details' => $details,
            ]
        );
    }
}
