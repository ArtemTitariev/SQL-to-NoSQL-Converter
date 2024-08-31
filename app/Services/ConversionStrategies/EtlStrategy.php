<?php

namespace App\Services\ConversionStrategies;

use App\Models\Convert;
use Illuminate\Http\Request;

class EtlStrategy implements ConversionStrategyInterface
{
    public function execute(Convert $convert, Request $request, array $extraParams = []): StrategyResult
    {
        // Логіка для кроку etl

        // Return success response
        return new StrategyResult (
            result: StrategyResult::STATUSES['COMPLETED'],
            details: 'elt strategy.',
        );
    }
}
