<?php

namespace App\Actions;

use App\Mail\ConvertCompleted;
use App\Models\Convert;
use App\Services\ConversionService;
use Illuminate\Support\Facades\Mail;

class CompleteConvert
{
    public static function execute(
        Convert $convert,
        string $message = 'Convert completed.',
    ) {
        ConversionService::completeConvert($convert, config('convert_steps.etl.key'), $message);
        Mail::to($convert->user)->send(new ConvertCompleted($convert));
    }
}
