<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Convert;
use App\Models\ConversionProgress;

class CheckStepAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {

        $convert = $request->route('convert');
        $step = $request->route('step');

        if (! $this->isStepAccessible($convert, $step)) {
            return redirect()->route('converts.show', ['convert' => $convert])->withErrors(['error' => __('You cannot skip steps.')]);
        }

        return $next($request);
    }

    protected function isStepAccessible(Convert $convert, string $step): bool
    {
        $steps = config('convert_steps');

        if (! isset($steps[$step])) {
            return false;
        }

        $lastCompletedStep = $convert->lastCompletedStep();

        if ($lastCompletedStep !== ($steps[$step]['number'] - 1)) {
            return false;
        }

        return true;
    }
}
