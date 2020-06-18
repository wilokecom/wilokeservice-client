<?php

namespace WilokeServiceClient\Helpers;

/**
 * Class App
 * @package WilokeServiceClient\Helpers
 */
class App
{
    private static $aRegistry = [];
    
    /**
     * @param $key
     * @param $val
     */
    public static function bind($key, $val)
    {
        self::$aRegistry[$key] = $val;
    }
    
    /**
     * @param $key
     *
     * @return bool|mixed
     */
    public static function get($key)
    {
        return array_key_exists($key, self::$aRegistry) ? self::$aRegistry[$key] : false;
    }
}
