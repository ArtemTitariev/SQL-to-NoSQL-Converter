<?php

namespace App\Services\ConversionStrategies;

use App\Models\Convert;
use Illuminate\Http\Request;

class EtlStrategy implements ConversionStrategyInterface
{
    public function execute(Convert $convert, Request $request, array $extraParams = [])
    {
        // Логіка для кроку etl

        // Return success response
        return [
            'status' => 'success',
            'details' => 'elt srategy.',
            'next' => null,
        ];
    }
}
