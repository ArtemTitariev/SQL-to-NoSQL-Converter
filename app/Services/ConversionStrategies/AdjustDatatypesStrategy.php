<?php

namespace App\Services\ConversionStrategies;

use App\Models\Convert;
use Illuminate\Http\Request;

class AdjustDatatypesStrategy implements ConversionStrategyInterface
{
    public function execute(Convert $convert, Request $request, array $extraParams = []): StrategyResult
    {
        // Логіка для кроку збереження типів даних

        // Return success response
        return new StrategyResult(
            result: StrategyResult::STATUSES['COMPLETED'],
            details: 'Adjust datatypes strategy.',
            next: config('convert_steps.adjust_datatypes.next'),
        );
    }
}
