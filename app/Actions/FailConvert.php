<?php

namespace App\Actions;

use App\Mail\ConvertFailed;
use App\Models\Convert;
use App\Services\ConversionService;
use Illuminate\Support\Facades\Mail;

class FailConvert
{

    public static function execute(
        Convert $convert,
        string $message = "An error occurred while loading, processing, or writing data.",
    ) {
        ConversionService::failConvert($convert, config('convert_steps.etl.key'), $message);
        Mail::to($convert->user)->send(new ConvertFailed($convert, __($message)));
    }
}
