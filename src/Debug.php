<?php

namespace TONYLABS\PowerSchool\Api;

class Debug
{
    public static function log($data)
    {
        if (config('app.debug') && function_exists('ray')) {
            if (is_callable($data)) {
                $data();
                return;
            }
            ray($data)->purple();
        }
    }
}