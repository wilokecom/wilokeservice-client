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

use WilokeServiceClient\Helpers\GetAPI;
use WilokeServiceClient\Helpers\PostAPI;
use WilokeServiceClient\Controllers\DownloadController;
use WilokeServiceClient\Controllers\NotificationController;
use WilokeServiceClient\Controllers\ScheduleCheckUpdateController;
use WilokeServiceClient\Controllers\Shortcodes;
use WilokeServiceClient\Controllers\UpdateController;
use WilokeServiceClient\Helpers\App;
use WilokeServiceClient\Helpers\RestAPI;
use WilokeServiceClient\RegisterMenu\RegisterWilcityServiceMenu;

define('WILOKESERVICE_CLIENT_DIR', plugin_dir_path(__FILE__));
define('WILOKESERVICE_CLIENT_VIEWS_DIR', plugin_dir_path(__FILE__) . 'app/Views/');
define('WILOKESERVICE_CLIENT_SOURCE', plugin_dir_url(__FILE__) . 'source/');
define('WILOKESERVICE_CLIENT_ASSSETS', plugin_dir_url(__FILE__) . 'assets/');
define('WILOKESERVICE_VERSION', '1.0');
define('WILOKESERVICE_DS', '/');

require plugin_dir_path(__FILE__) . 'vendor/autoload.php';

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

if (!function_exists('wilokeServiceClientGetConfigFile')) {
	/**
	 * @param $file
	 *
	 * @return mixed
	 */
	function wilokeServiceClientGetConfigFile($file)
	{
		$aConfig = include plugin_dir_path(__FILE__) . 'configs/' . $file . '.php';

		return $aConfig;
	}
}

new RegisterWilcityServiceMenu();
new UpdateController();
//new ScheduleCheckUpdateController();
new DownloadController();
//new NotificationController();
//new Shortcodes();
App::bind('rest', new RestAPI());
App::bind('getAPI', new GetAPI());
App::bind('postAPI', new PostAPI());

/**
 * @return RestAPI
 */
function wilcityServiceRestRequest()
{
	return App::get('rest');
}

/**
 * @return GetAPI
 */
function wilcityServiceGetRequest()
{
	return App::get('getAPI');
}

/**
 * @return PostAPI
 */
function wilcityServicePostRequest()
{
	return App::get('postAPI');
}

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

