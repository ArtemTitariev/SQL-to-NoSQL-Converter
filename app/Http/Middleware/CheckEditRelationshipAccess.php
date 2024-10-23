<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckEditRelationshipAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $convert = $request->route('convert');
        $lastStep = $convert->lastProgress();

        if (!$lastStep->canContinue() || !$lastStep->name === config('convert_steps.adjust_relationships.name')) {
            return redirect()->route('converts.show', ['convert' => $convert])->withErrors(['error' => __('You cannot skip steps.')]);
        }

        return $next($request);
    }
}
