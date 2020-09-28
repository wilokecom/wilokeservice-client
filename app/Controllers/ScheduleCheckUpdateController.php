<?php

namespace WilokeServiceClient\Controllers;

use WilokeServiceClient\Helpers\ThemeInformation;
use function GuzzleHttp\Psr7\str;
use WilokeServiceClient\Helpers\General;
use WilokeServiceClient\Helpers\RestAPI;

/**
 * Class ScheduleCheckUpdateController
 * @package WilokeServiceClient\Controllers
 */
class ScheduleCheckUpdateController
{
    protected static $checkUpdateKey = 'wilokeservice_check_update';
    
    public function __construct()
    {
        add_action(self::$checkUpdateKey, [$this, 'checkUpdateTwiceDaily']);
        add_action('admin_notices', [$this, 'noticeHasUpdate']);
        add_action('admin_init', [$this, 'focusClearHasUpdate']);
    }
    
    public function focusClearHasUpdate()
    {
        if (General::isWilcityServicePage()) {
            delete_option('wilokeservice_has_update');
        }
    }
    
    private function hasUpdate()
    {
        update_option('wilokeservice_has_update', true);
    }
    
    /**
     * @return bool
     */
    public function noticeHasUpdate()
    {
        if (!get_option('wilokeservice_has_update')) {
            return false;
        }
        
        $class    = 'notice notice-error';
        $adminUrl = add_query_arg(
            [
	            'page'              => wilokeServiceClientGetConfigFile('app')['updateSlug'],
	            'is-refresh-update' => 'yes'
            ], admin_url('admin.php'));
        
        $message =
            'There is a new update of the theme. Please click on <a href="'.$adminUrl.'">Update</a> to update it';
        
        printf('<div class="%1$s"><p>%2$s</p></div>', esc_attr($class), $message);
    }
    
    /**
     * @param $pluginID
     *
     * @return string
     */
    private function buildPluginPathInfo($pluginID)
    {
        return $pluginID.'/'.$pluginID.'.php';
    }
    
    /**
     * @return bool
     */
    public function checkUpdateTwiceDaily()
    {
        $oTheme      = wp_get_theme();
        $themeAuthor = strtolower(trim($oTheme->get('Author')));
        
        if (strpos($themeAuthor, 'wiloke') === false) {
            return false;
        }
        
        $aResponse = RestAPI::get(RestAPI::buildThemeEndpoint(ThemeInformation::getThemeSlug()));
        
        if ($aResponse['status'] == 'success') {
            $aPlugins = isset($aResponse['aPlugins']) ? $aResponse['aPlugins'] : false;
            $aTheme   = isset($aResponse['aTheme']) ? $aResponse['aTheme'] : false;
            
            if ($aTheme) {
                if (version_compare($aTheme['version'], $oTheme->get('Version'), '>')) {
                    $this->hasUpdate();
                    
                    return true;
                }
            }
            
            foreach ($aPlugins as $aPlugin) {
                $aThisPluginInfoOnSite = get_plugin_data($this->buildPluginPathInfo($aPlugin['slug']));
                if (empty($aThisPluginInfoOnSite)) {
                    continue;
                }
                
                if (version_compare($aPlugin['version'], $aThisPluginInfoOnSite['Version'], '>')) {
                    $this->hasUpdate();
                    
                    return true;
                }
            }
        }
    }
    
    public static function setupCheckUpdateTwiceDaily()
    {
        if (!wp_next_scheduled(self::$checkUpdateKey)) {
            wp_schedule_event(time(), 'twicedaily', self::$checkUpdateKey);
        }
    }
    
    public static function clearCheckUpdateTwiceDaily()
    {
        wp_clear_scheduled_hook(self::$checkUpdateKey);
    }
}
