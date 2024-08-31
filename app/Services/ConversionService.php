<?php

namespace App\Services;

use App\Models\Convert;
use App\Models\ConversionProgress;

class ConversionService
{
    /**
     * Array of steps from config
     * @param array
     */
    private static $steps;

    private static function initializeSteps()
    {
        if (is_null(self::$steps)) {
            self::$steps = config('convert_steps');
        }
    }

    /**
     * Crate new conversion progress
     *
     * @param \App\Models\Convert $convert
     * @param string $step
     * @param string $status
     * @param string $dateils
     * @return void
     */
    public static function createConversionProgress(Convert $convert, string $step, string $status, string $details)
    {
        self::initializeSteps();

        ConversionProgress::create([
            'convert_id' => $convert->id,
            'step' => static::$steps[$step]['number'],
            'name' => static::$steps[$step]['name'],
            'status' => $status,
            'details' => $details,
        ]);

        $convert->touch();
    }

    /**
     * Update or crate conversion progress
     *
     * @param \App\Models\Convert $convert
     * @param string $step
     * @param string $status
     * @param string $dateils
     * @return void
     */
    public static function updateConversionProgress(Convert $convert, string $step, string $status, string $details)
    {
        self::initializeSteps();

        ConversionProgress::updateOrCreate(
            [
                'convert_id' => $convert->id,
                'step' => static::$steps[$step]['number'],
                'name' => static::$steps[$step]['name'],
            ],
            [
                'status' => $status,
                'details' => $details,
            ]
        );

        $convert->touch();
    }

    /**
     * Fail current profress and whole convert
     *
     * @param \App\Models\Convert $convert
     * @param string $step
     * @param string $message
     * @return void
     * @throws \Exception
     */
    public static function failConvert($convert, $step, $message)
    {
        ConversionService::updateConversionProgress(
            $convert,
            $step,
            ConversionProgress::STATUSES['ERROR'],
            $message
        );

        $convert->fail();
    }
}
