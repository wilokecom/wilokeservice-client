<?php

namespace WilokeServiceClient\Helpers;

use WilokeServiceClient\RegisterMenu\RegisterWilcityServiceMenu;

class Option
{
    static $aOptions = null;
    
    public static function getOptions()
    {
        if (!empty(self::$aOptions)) {
            return self::$aOptions;
        }
        
        self::$aOptions = maybe_unserialize(get_option(RegisterWilcityServiceMenu::$optionKey));
        
        return self::$aOptions;
    }
    
    public static function getOptionField($field, $default = '')
    {
        self::getOptions();
        
        return isset(self::$aOptions[$field]) ? self::$aOptions[$field] : $default;
    }
}
