<?php

namespace App\Services\ConversionStrategies;

use App\Models\Convert;
use Illuminate\Http\Request;

class AdjustDatatypesStrategy implements ConversionStrategyInterface
{
    public function execute(Convert $convert, Request $request, array $extraParams = [])
    {
        // Логіка для кроку збереження типів даних

         // Return success response
         return [
            'status' => 'success',
            'details' => 'Adjust datatypes srategy.',
            'next' => config('convert_steps.adjust_datatypes.next'),
        ];
    }
}