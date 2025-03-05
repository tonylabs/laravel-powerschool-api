<?php

namespace TONYLABS\PowerSchool\Api;

class Debug
{
    /**
     * Log data when in debug mode
     * 
     * @param mixed $data Data to log or callable that produces debug output
     * @return void
     */
    public static function log($data): void
    {
        // Early return if not in debug mode or ray isn't available
        if (!config('app.debug') || !function_exists('ray')) {
            return;
        }
        
        if (is_callable($data)) {
            $data();
            return;
        }
        
        ray($data)->purple();
    }
    
    /**
     * Log data with a specific color
     * 
     * @param mixed $data Data to log
     * @param string $color Ray color to use
     * @return void
     */
    public static function logWithColor($data, string $color = 'purple'): void
    {
        if (!config('app.debug') || !function_exists('ray')) {
            return;
        }
        
        $rayInstance = ray($data);
        if (method_exists($rayInstance, $color)) {
            $rayInstance->$color();
        } else {
            $rayInstance->purple();
        }
    }
}