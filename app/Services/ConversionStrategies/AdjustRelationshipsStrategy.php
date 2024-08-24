<?php

namespace App\Services\ConversionStrategies;

use App\Models\Convert;
use Illuminate\Http\Request;

class AdjustRelationshipsStrategy implements ConversionStrategyInterface
{
    public function execute(Convert $convert, Request $request, array $extraParams = [])
    {
        // Логіка для кроку збереження зв'язків

        // Return success response
        return [
            'status' => 'success',
            'details' => 'Adjust relationships srategy.',
            'next' => config('convert_steps.adjust_relationships.next'),
        ];
    }
}
