<?php

namespace App\Services\ConversionStrategies;

use App\Models\Convert;
use Illuminate\Http\Request;

class AdjustRelationshipsStrategy implements ConversionStrategyInterface
{
    public function execute(Convert $convert, Request $request, array $extraParams = []): StrategyResult
    {
        // Логіка для кроку збереження зв'язків



        dd('strategy execute');

        // Return success response
        return new StrategyResult(
            result: StrategyResult::STATUSES['COMPLETED'],
            details: 'Adjust relationships strategy.',
            next: config('convert_steps.adjust_relationships.next'),
        );
    }
}
