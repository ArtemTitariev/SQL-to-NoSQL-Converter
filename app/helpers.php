<?php

use Carbon\Carbon;

if (! function_exists('datetimeToStr')) {
    /**
     * Return all the values of an array without indexes.
     * @var array $data
     * 
     * @return array
     */
    function datetimeToStr(Carbon $carbon): string
    {
        return $carbon->format('Y-m-d H:i:s');
    }
}
