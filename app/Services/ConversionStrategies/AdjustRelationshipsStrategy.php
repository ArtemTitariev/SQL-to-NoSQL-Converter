<?php

namespace App\Services\ConversionStrategies;

use App\Models\Convert;
use Illuminate\Http\Request;

class AdjustRelationshipsStrategy implements ConversionStrategyInterface
{
    public function execute(Convert $convert, Request $request, array $extraParams = []): StrategyResult
    {
        // Коригування зв'язків вже виконано

        $convert->updateStatus(Convert::STATUSES['IN_PROGRESS']);

        $nextStep = config('convert_steps.adjust_relationships.next');

        // Return success response
        return new StrategyResult(
            result: StrategyResult::STATUSES['COMPLETED'],
            details: 'Adjusting of relationships between collections is complete.',
            next: $nextStep,
        );
    }
}
