<?php

namespace App\Services\ConversionStrategies;

use App\Models\Convert;
use Illuminate\Http\Request;

interface ConversionStrategyInterface
{
    public function execute(Convert $convert, Request $request, array $extraParams = []);
}