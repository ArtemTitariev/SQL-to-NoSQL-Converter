<?php

use Carbon\Carbon;

if (! function_exists('datetimeToStr')) {
    /**
     * Format datetime object to string 'Y-m-d H:i:s'.
     * @var Carbon $carbon
     * 
     * @return string
     */
    function datetimeToStr(Carbon $carbon): string
    {
        return $carbon->format('Y-m-d H:i:s');
    }
}

if (! function_exists('isWithinRowNumberLimit')) {
    /**
     * Check if the number of rows is within the allowed limit for 
     * MongoDB collections.
     * 
     * The function compares the passed number of rows with the maximum 
     * allowed value.
     * 
     * @param int $rowsNumber The number of rows to check.
     * 
     * @return bool Returns true if the number of rows does not exceed the 
     * limit and is not equal to the value indicating that the limit is 
     * exceeded; otherwise - false.
     */
    function isWithinRowNumberLimit(int $rowsNumber): bool
    {
        return $rowsNumber !== config('constants.MAX_ROWS_LIMIT_EXCEEDED') &&
            $rowsNumber <= config('constants.MAX_COLLECTION_EMBEDDED_FIELDS');
    }
}

if (! function_exists('isJsonString')) {
    function isJsonString($string)
    {
        if (!is_string($string)) {
            return false;
        }

        json_decode($string);

        return json_last_error() === JSON_ERROR_NONE;
    }
}
