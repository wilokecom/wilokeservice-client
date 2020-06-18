<?php
/**
 * Plugin Name: WilokeService Client
 * Plugin URI: https://wilcityservice.com/
 * Description: Easily update Wiloke Theme
 * Version: 1.0
 * Author: Wiloke
 * Author URI: https://wiloke.com
 * Text Domain: wilokeservice
 * Domain Path: /i18n/languages/
 *
 * @package wilcity
 */

define('WILOKESERVICE_CLIENT_DIR', plugin_dir_path(__FILE__));
define('WILOKESERVICE_CLIENT_SOURCE', plugin_dir_url(__FILE__).'source/');
define('WILOKESERVICE_CLIENT_ASSSETS', plugin_dir_url(__FILE__).'assets/');
define('WILOKESERVICE_VERSION', '1.0');
define('WILOKESERVICE_DS', '/');

require plugin_dir_path(__FILE__).'vendor/autoload.php';

register_activation_hook(__FILE__, 'wilokeServiceRegisterScheduleHook');
if (!function_exists('wilokeServiceRegisterScheduleHook')) {
    function wilokeServiceRegisterScheduleHook()
    {
        if (!wp_next_scheduled('wilokeservice_hourly_event')) {
            wp_schedule_event(time(), 'hourly', 'wilokeservice_hourly_event');
        }
    }
}
register_deactivation_hook(__FILE__, 'wilokeServiceUnRegisterScheduleHook');
if (!function_exists('wilokeServiceUnRegisterScheduleHook')) {
    function wilokeServiceUnRegisterScheduleHook()
    {
        wp_clear_scheduled_hook('wilokeservice_hourly_event');
    }
}

if (!function_exists('wilokeServiceGetConfigFile')) {
    /**
     * @param $file
     *
     * @return mixed
     */
    function wilokeServiceGetConfigFile($file)
    {
        $aConfig = include plugin_dir_path(__FILE__).'configs/'.$file.'.php';
        
        return $aConfig;
    }
}

new \WilokeServiceClient\RegisterMenu\RegisterWilcityServiceMenu();
new \WilokeServiceClient\Controllers\UpdateController();
new \WilokeServiceClient\Controllers\ScheduleCheckUpdateController();
new \WilokeServiceClient\Controllers\DownloadController();
new \WilokeServiceClient\Controllers\NotificationController();
new \WilokeServiceClient\Controllers\Shortcodes();

register_activation_hook(
    __FILE__,
    [
        '\WilokeServiceClient\Controllers\ScheduleCheckUpdateController',
        'setupCheckUpdateTwiceDaily'
    ]
);
register_deactivation_hook(
    __FILE__,
    [
        '\WilokeServiceClient\Controllers\ScheduleCheckUpdateController',
        'clearCheckUpdateTwiceDaily'
    ]
);

do_action('wilokeservice/loaded');

