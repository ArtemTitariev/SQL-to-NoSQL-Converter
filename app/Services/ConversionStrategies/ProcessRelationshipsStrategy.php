<?php

namespace App\Services\ConversionStrategies;

use App\Models\Convert;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

use App\Jobs\ProcessRelationships as ProcessRelationshipsJob;

class ProcessRelationshipsStrategy implements ConversionStrategyInterface
{

    public function execute(Convert $convert, Request $request, array $extraParams = []): StrategyResult
    {
        ProcessRelationshipsJob::dispatch(
            Auth::user(),
            $convert,
            config('convert_steps.process_relationships.key')
        )->onQueue('process_relationships')
            ->delay(now()->addSeconds(2)); /////////////////////

        return new StrategyResult(
            result: StrategyResult::STATUSES['PROCESSING'],
            details: 'Primary processing of relationships is performed.',
            route: config('convert_steps.process_relationships.route'),
            // view: 'convert.process_relationships-loading',
            // with: ['convert' => $convert],
        );
    }
}
