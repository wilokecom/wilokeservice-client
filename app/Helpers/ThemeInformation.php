<?php

namespace WilokeServiceClient\Helpers;

/**
 * Class ThemeInformation
 * @package WilokeServiceClient\Helpers
 */
class ThemeInformation
{
    /**
     * @return array|false|string
     */
    public static function getThemeSlug()
    {
        $oTheme   = wp_get_theme();
        $template = $oTheme->get('Author');
        if (strpos($template, 'child') === false && strtolower($template)) {
            $themeName = strtolower($oTheme->get('Template'));
        } else {
            $themeName = $oTheme->get('Name');
        }
        
        return $themeName;
    }
}
