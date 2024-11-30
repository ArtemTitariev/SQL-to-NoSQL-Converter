<?php

namespace App\Http\Handlers;

class StepResultHandler
{
    public static function handle($result)
    {
        if (
            $result instanceof \App\Services\ConversionStrategies\StrategyResult &&
            $result->isFailed()
        ) {
            return back()->withInput()->withErrors($result->getDetails());
        }

        if (
            $result instanceof \Illuminate\Http\RedirectResponse ||
            $result instanceof \Illuminate\Contracts\View\View
        ) {
            return $result;
        }

        return redirect()->route('converts.index');
    }
}
