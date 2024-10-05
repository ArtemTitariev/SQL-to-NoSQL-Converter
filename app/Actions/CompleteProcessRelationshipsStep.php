<?php

namespace App\Actions;

use App\Models\ConversionProgress;
use App\Models\Convert;
use App\Services\ConversionService;

class CompleteProcessRelationshipsStep
{
    /**
     * Complete ProcessRelationships step, create next step with `configuring` status -------
     *
     * @param Convert $convert
     * @param string $step
     * 
     * @return void
     */

    public static function execute(Convert $convert, string $step, bool $hasRelations): void
    {
        ConversionService::updateConversionProgress(
            $convert,
            // config('convert_steps.read_schema.key'),
            $step,
            ConversionProgress::STATUSES['COMPLETED'],
            'Relationships have been processed.'
        );

        // Отримуємо наступний крок із конфігурації
        $nextStep = config("convert_steps.{$step}.next");

        // -----------------------------------------------------------------
        if ($hasRelations) {
            // pefrorm next step
            ConversionService::createConversionProgress(
                $convert,
                $nextStep,
                ConversionProgress::STATUSES['CONFIGURING'],
                'Step in the configuration process.'
            );
        } else {
            // 'skip' next step and perform ETL
            ConversionService::createConversionProgress(
                $convert,
                $nextStep,
                ConversionProgress::STATUSES['COMPLETED'],
                'There is no relationships. The step has been skipped.'
            );
            
            $nextStep = config("convert_steps.{$nextStep}.next");
            ConversionService::createConversionProgress(
                $convert,
                $nextStep,
                ConversionProgress::STATUSES['PENDING'],
                'Step in the pending process.'
            );
        }

        


        
    }
}
