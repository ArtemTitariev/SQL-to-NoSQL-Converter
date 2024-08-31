<?php

namespace App\Actions;

use App\Models\ConversionProgress;
use App\Models\Convert;
use App\Services\ConversionService;

class CompleteSchemaReadingStep
{
    /**
     * Complete schema reading step, create nex step with `configuring` status 
     *
     * @param Convert $convert
     * @param string $step
     * 
     * @return void
     */

    public static function execute(Convert $convert, string $step): void
    {
        ConversionService::updateConversionProgress(
            $convert,
            // config('convert_steps.read_schema.key'),
            $step,
            ConversionProgress::STATUSES['COMPLETED'],
            'Relational database schema has been analyzed.'
        );

        // Отримуємо наступний крок із конфігурації
        $nextStep = config("convert_steps.{$step}.next");

        if ($nextStep) {
            ConversionService::createConversionProgress(
                $convert,
                $nextStep,
                ConversionProgress::STATUSES['CONFIGURING'],
                'Step in the configuration process.'
            );
        }
    }
}
