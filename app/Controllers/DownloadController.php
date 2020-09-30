<?php

namespace WilokeServiceClient\Controllers;

use WilokeServiceClient\Controllers\Download\AbstractDownload;
use WilokeServiceClient\Controllers\Download\DownloadPlugin;
use WilokeServiceClient\Controllers\Download\DownloadTheme;
use WilokeServiceClient\Helpers\Download;
use WilokeServiceClient\Helpers\Option;

/**
 * Class DownloadController
 * @package WilokeServiceClient\Controllers
 */
class DownloadController extends AbstractDownload
{
	private $extPath;
	private $pluginPath;

	public function __construct()
	{
		add_action('wp_ajax_wiloke_download_theme', [$this, 'downloadItem']);
		add_action('wp_ajax_wiloke_download_plugin', [$this, 'downloadItem']);
		add_action('wp_ajax_wiloke_activate_plugin', [$this, 'activatePlugin']);
		add_action('wp_ajax_wiloke_activate_theme', [$this, 'activateTheme']);
		add_action('wp_ajax_wiloke_deactivate_plugin', [$this, 'deactivatePlugin']);
		add_action('upgrader_package_options', [$this, 'addBearTokenToDownloadURL']);
		add_filter('http_request_args', [$this, 'addWilokeServiceClientVersionToHeader'], 10, 2);

		$this->setPaths();
	}

	public function addWilokeServiceClientVersionToHeader($aParseArgs, $url)
	{
		if (strpos($url, wilokeServiceClientGetConfigFile('app')['baseURL']) !== false) {
			$aParseArgs['WILOKE_SERVICE_VERSION'] = WILOKESERVICE_VERSION;
		}

		return $aParseArgs;
	}

	/**
	 * @param $aOptions
	 *
	 * @return mixed
	 */
	public function addBearTokenToDownloadURL($aOptions)
	{
		if (isset($aOptions['package']) &&
			strpos($aOptions['package'], wilokeServiceClientGetConfigFile('app')['serviceURL']) !== false) {
			$aOptions['package'] = add_query_arg(
				[
					'token' => Option::getOptionField('secret_token')
				],
				$aOptions['package']
			);
		}

		return $aOptions;
	}

	private function setPaths()
	{
		$this->extPath = WP_CONTENT_DIR . WILOKESERVICE_DS . 'uploads' . WILOKESERVICE_DS . 'wiloke_extensions'
			. WILOKESERVICE_DS;
		$this->pluginPath = dirname(WILOKESERVICE_CLIENT_DIR) . WILOKESERVICE_DS;
	}

	protected function verify()
	{
		if (!check_ajax_referer('wiloke-service-nonce', 'security', false)) {
			wp_send_json_error([
				'msg' => 'Invalid Security code'
			]);
		}

		if (!current_user_can('administrator')) {
			wp_send_json_error([
				'msg' => 'You do not have permission to access this page'
			]);
		}

		if (!$this->extPath) {
			wp_send_json_error([
				'msg' => 'Error: Could not create extensions folder'
			]);
		}
	}

	public function deactivatePlugin()
	{
		$this->verify();

		$current = get_option('active_plugins');
		$plugin = trim($_POST['itemPath']);
		if (in_array($plugin, $current)) {
			$current = array_flip($current);
			unset($current[$plugin]);
			$current = array_keys($current);

			update_option('active_plugins', $current);
			wp_send_json_success(['msg' => 'Congrats, The plugin has been deactivated']);
		}

		wp_send_json_success(['msg' => 'The plugin was disabled already']);
	}

	public function activatePlugin()
	{
		$this->verify();

		$current = get_option('active_plugins');
		$plugin = trim($_POST['itemPath']);

		if (!in_array($plugin, $current)) {
			$current[] = $plugin;
			sort($current);
			update_option('active_plugins', $current);

			wp_send_json_success(['msg' => 'Congrats, The plugin has been activated']);
		}

		wp_send_json_success(['msg' => 'The plugin is activating already']);
	}

	public function activateTheme() {
		$this->verify();

		$oTheme = wp_get_theme( $_POST['item'] );

		if ( ! $oTheme->exists() || ! $oTheme->is_allowed() ) {
			wp_send_json_error(
				[
					'msg' => 'Something went wrong, We could not active this theme.'
				]
			);
		}

		switch_theme( $oTheme->get_stylesheet() );
		wp_send_json_success(['msg' => sprintf('The %s has been activated', $oTheme->get('Name'))]);
	}

	public function downloadItem()
	{
		$this->verify();

		if ($_POST['itemType'] == 'theme') {
			$oDownloadItem = new DownloadTheme();
		} else {
			$oDownloadItem = new DownloadPlugin();
		}

		$oDownloadItem->setItemName($_POST['item']);

		$aResponse = $this->install($oDownloadItem);

		if ($aResponse['status'] == 'success') {
			wp_send_json_success($aResponse);
		} else {
			wp_send_json_error($aResponse);
		}
	}
}
