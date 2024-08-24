<?php

namespace App\Http\Handlers;

class StepResultHandler
{
    public static function handle($result)
    {
        if (is_array($result) && $result['status'] === 'failed') {
            return back()->withInput()->withErrors($result['error']);
        }

        if ($result instanceof \Illuminate\Http\RedirectResponse) {
            return $result;
        }

        dd('StepResultHendler not array and not redirect'); //------------
        return redirect()->route('converts.index');
    }
}
