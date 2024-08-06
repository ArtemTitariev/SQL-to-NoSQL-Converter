<?php

if (! function_exists('values')) {
    /**
     * Return all the values of an array without indexes.
     * @var array $data
     * 
     * @return array
     */
    function values(array &$data) {
        $result = [];
        foreach ($data as $k => $v){
            array_push($result, $v);
        }

        return $result;
    }
}