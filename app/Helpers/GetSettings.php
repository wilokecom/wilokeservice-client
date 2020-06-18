<?php

namespace WilokeServiceClient\Helpers;

class GetSettings
{
    static $aOptions = null;
    
    public static function getOptions()
    {
        if (self::$aOptions !== null) {
            return self::$aOptions;
        }
        
        self::$aOptions = maybe_unserialize(get_option('wilokeservice_client'));
        
        return self::$aOptions;
    }
    
    public static function getOptionField($field)
    {
        self::getOptions();
        
        return isset(self::$aOptions[$field]) ? self::$aOptions[$field] : '';
    }
}
