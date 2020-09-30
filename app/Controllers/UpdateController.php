<?php

namespace WilokeServiceClient\Controllers;

use WilokeService\RestApi\GetAPI;
use WilokeServiceClient\Helpers\App;
use WilokeServiceClient\Helpers\Option;
use WilokeServiceClient\Helpers\ThemeInformation;
use WilokeServiceClient\RegisterMenu\RegisterWilcityServiceMenu;
use function Sodium\compare;
use WilokeServiceClient\Helpers\General;
use WilokeServiceClient\Helpers\RestAPI;

/**
 * Class UpdateController
 * @package WilokeServiceClient\Controllers
 */
class UpdateController
{
	private $aPlugins;
	private $aTheme;
	private $isFocusGetUpdates              = false;
	private $aResponse;
	private $responseCode;
	private $aInstalledPlugins;
	private $oCurrentThemeVersion           = null;
	private $cacheUpdateKeys                = 'wilokeservice_cache_updates';
	private $saveUpdateInfoIn               = 300;
	private $changeLogURL                   = 'https://wiloke.net/themes/changelog/8';
	private $phpRequired                    = '7.2';
	private $aStatusCodeNoNeedToPrintUpdate = ['CLIENT_WEBSITE_IS_INVALID', 'INVALID_TOKEN', 'IP_BLOCKED'];
	public  $errMgs                         = '';

	public function __construct()
	{
		add_action('admin_init', [$this, 'getUpdates'], 1);

		add_filter('http_request_args', [$this, 'updateCheck'], 5, 2);
		add_action('wilokeservice-clients/before/theme-updates', [$this, 'renderHeading'], 1);
		add_action('wilokeservice-clients/after/theme-updates', [$this, 'openUpdateForm'], 1);
		add_action('wilokeservice-clients/after/theme-updates', [$this, 'selectThemes']);
		add_action('wilokeservice-clients/after/theme-updates', [$this, 'showUpPlugins'], 20);
		add_action('wilokeservice-clients/after/theme-updates', [$this, 'closeUpdateForm'], 30);
		add_action('admin_enqueue_scripts', [$this, 'enqueueScripts']);

		add_filter('pre_set_site_transient_update_plugins', [$this, 'updatePlugins']);
		add_filter('pre_set_transient_update_plugins', [$this, 'updatePlugins']);

		add_filter('pre_set_site_transient_update_themes', [$this, 'updateThemes'], 1, 99999);
		add_filter('pre_set_transient_update_themes', [$this, 'updateThemes'], 1, 99999);

		add_action('admin_init', [$this, 'checkUpdatePluginDirectly'], 10);
		add_filter('http_request_args', [$this, 'addBearTokenToHeaderDownload'], 10, 2);

		add_action('wp_ajax_wiloke_reupdate_response_of_theme', [$this, 'reUpdateResponseOfTheme']);
		add_action('wp_ajax_wiloke_reupdate_response_of_plugins', [$this, 'reUpdateResponseOfPlugins']);
		add_action('admin_init', [$this, 'clearUpdatePluginTransients'], 1);

		add_action('after_switch_theme', [$this, 'afterSwitchTheme']);
		add_action('activated_plugin', [$this, 'afterActivatePlugin']);
		add_action('admin_init', [$this, 'focusRequestStatistic']);
		//        add_action('admin_init', [$this, 'testHandleStatistic']);
	}

	private function isEnteredToken()
	{
		return !empty(get_option(RegisterWilcityServiceMenu::$tokenProvidedKey));
	}

	public function generateLink(array $aArgs)
	{
		$aArgs['page'] = wilokeServiceClientGetConfigFile('app')['menu']['slug'];
		return add_query_arg($aArgs, admin_url('admin.php'));
	}

	public function clearUpdatePluginTransients()
	{
		global $pagenow;
		if (($pagenow == 'plugins.php' || $pagenow == 'network-plugins.php' || $pagenow == 'update-core.php' ||
				$pagenow == 'network-update-core.php') && !General::isWilokeServicePage()
		) {
			if (get_option('wiloke_clear_update_plugins')) {
				delete_site_transient('update_plugins');
				delete_option('wiloke_clear_update_plugins');
			}
		}
	}

	/**
	 * @param $r
	 * @param $url
	 *
	 * @return mixed
	 */
	public function addBearTokenToHeaderDownload($r, $url)
	{
		if (strpos($url, wilokeServiceClientGetConfigFile('app')['serviceURL']) !== false) {
			$r['headers']['authorization'] = RestAPI::getBearToken();
			$r['headers']['cache-control'] = 'no';
		}

		return $r;
	}

	/**
	 * @return bool
	 */
	private function isFocus()
	{
		return (isset($_REQUEST['is-refresh-update']) && $_REQUEST['is-refresh-update'] == 'yes') ||
			$this->isFocusGetUpdates;
	}

	private function requestUpdate()
	{
		if (!$this->isEnteredToken()) {
			return false;
		}

		$aThemeResponse = wilokeServiceRestRequest()->request(wilokeServiceGetRequest()->setEndpoint('themes/'
			. ThemeInformation::getThemeSlug()))->getResponse();

		if ($aThemeResponse['status'] === 'success') {
			$this->aResponse['status'] = 'success';
			$this->aResponse['aTheme'] = $aThemeResponse['item'];
			$this->aTheme = $aThemeResponse['item'];

			$aPluginResponse = wilokeServiceRestRequest()->request(wilokeServiceGetRequest()->setEndpoint('themes/'
				. ThemeInformation::getThemeSlug() . '/plugins'))->getResponse();

			if ($aPluginResponse['status'] === 'error') {
				$this->aResponse['aPlugins'] = [];
			} else {
				foreach ($aPluginResponse['items'] as $aPlugin) {
					$this->aPlugins[$this->buildPluginPathInfo($aPlugin['slug'])] = $aPlugin;
				}

				$this->aResponse['aPlugins'] = $this->aPlugins;
			}

			set_transient($this->cacheUpdateKeys, $this->aResponse, $this->saveUpdateInfoIn);
		} else {
			$this->aPlugins = false;
			$this->aTheme = false;
			$this->aResponse['status'] = 'error';
			$this->aResponse['msg'] = isset($aThemeResponse['msg']) ? $aThemeResponse['msg'] : '';
			set_transient($this->cacheUpdateKeys, $this->aResponse, $this->saveUpdateInfoIn * 20);
		}
	}

	private function getUpdatesFromCache()
	{
		$this->aResponse = get_transient($this->cacheUpdateKeys);
		if (!empty($this->aResponse)) {
			if ($this->aResponse['status'] == 'error') {
				$this->aPlugins = [];
				$this->aTheme = [];
				$this->errMgs = isset($this->aResponse['msg']) ? $this->aResponse['msg'] : '';
			} else {
				$this->aPlugins = $this->aResponse['aPlugins'];
				$this->aTheme = $this->aResponse['aTheme'];
			}
			$this->responseCode = isset($this->aResponse['code']) ? $this->aResponse['code'] : 'OKE';

			return $this->aResponse;
		}

		return [];
	}

	/**
	 * @param $aRequest
	 * @param $url
	 *
	 * @return mixed
	 */
	public function updateCheck($aRequest, $url)
	{

		// Plugin update request.
		if (false !== strpos($url, '//api.wordpress.org/plugins/update-check/1.1/')) {

			// Decode JSON so we can manipulate the array.
			$oData = json_decode($aRequest['body']['plugins']);

			// Remove the Envato Market.
			unset($oData->plugins->{'wilokeservice-client/wilokeservice-client.php'});

			// Encode back into JSON and update the response.
			$aRequest['body']['plugins'] = wp_json_encode($oData);
		}

		return $aRequest;
	}

	/**
	 * @return bool
	 */
	private function directlyUpdatePlugins()
	{
		if (empty($this->aPlugins)) {
			return false;
		}

		$oListPluginsInfo = new \stdClass();
		$oListPluginsInfo->response = [];
		$oListPluginsInfo->checked = [];

		$this->getListOfInstalledPlugins();

		$hasUpdate = false;
		$oUpdatesPlugins = get_site_transient('update_plugins');

		foreach ($this->aInstalledPlugins as $file => $aPlugin) {
			if (!isset($this->aPlugins[$file]) || empty($this->aPlugins[$file])) {
				if (isset($oUpdatesPlugins->checked) && isset($oUpdatesPlugins->checked[$file])) {
					$oListPluginsInfo->checked[$file] = $oUpdatesPlugins->checked[$file];
					if (isset($oUpdatesPlugins->response) && is_array($oUpdatesPlugins->response)) {
						if (isset($oUpdatesPlugins->response[$file])) {
							$oListPluginsInfo->response[$file] = $oUpdatesPlugins->response[$file];
						}
					}
				} else {
					$oListPluginsInfo->checked[$file] = $aPlugin['Version'];
				}
			} else {
				if (!isset($oListPluginsInfo->checked[$file]) || version_compare($aPlugin['Version'],
						$this->aPlugins[$file]['version'], '<')
				) {
					$oListPluginsInfo->response[$file] = $this->buildUpdatePluginSkeleton($this->aPlugins[$file]);
					$oListPluginsInfo->checked[$file] = $this->aInstalledPlugins[$file]['Version'];
					$hasUpdate = true;
				}
			}
		}

		if ($hasUpdate) {
			$oListPluginsInfo->last_checked = strtotime('+30 minutes');
			set_site_transient('update_plugins', $oListPluginsInfo);
			update_option('wiloke_clear_update_plugins', true);
			$this->setLastCheckedUpdatePlugins();
		}
	}

	/**
	 * @return bool
	 */
	public function directlyUpdateTheme()
	{
		if (empty($this->aTheme)) {
			return false;
		}

		$oMyTheme = wp_get_theme($this->aTheme['slug']);

		if (!$oMyTheme->exists()) {
			return false;
		}

		if (version_compare($oMyTheme->get('Version'), $this->aTheme['version'], '<')) {
			$oListThemesInfo = new \stdClass();
			$oListThemesInfo->response = [];
			$oListThemesInfo->checked = [];

			$oTheme['theme'] = $this->aTheme['slug'];
			$oTheme['new_version'] = $this->aTheme['version'];
			$oTheme['package'] = $this->aTheme['download'];

			$oListThemesInfo->response[$this->aTheme['slug']] = $oTheme;
			$oListThemesInfo->checked[$this->aTheme['version']] = $oMyTheme->get('Version');
			$oListThemesInfo->last_checked = strtotime('+30 minutes');
			set_site_transient('update_themes', $oListThemesInfo);
		}
	}

	/**
	 * @return bool
	 */
	public function checkUpdatePluginDirectly()
	{
		if (!General::isWilokeServicePage() || !$this->isNeededToRecheckUpdatePlugins()) {
			return false;
		}

		$this->directlyUpdateTheme();
		$this->directlyUpdatePlugins();
	}

	private function setLastCheckedUpdatePlugins()
	{
		set_transient('wiloke_last_checked_plugins_update', 'yes', 60 * 10);
	}

	/**
	 * @return bool
	 */
	private function isNeededToRecheckUpdatePlugins()
	{
		if ($this->isFocus()) {
			return true;
		}
		$lastChecked = get_transient('wiloke_last_checked_plugins_update');

		return $lastChecked != 'yes' || (defined('WILOKE_FOCUS_CHECKUPDATE') && WILOKE_FOCUS_CHECKUPDATE);
	}

	public function getUpdates()
	{
		global $pagenow;
		if (General::isWilokeServicePage() ||
			($pagenow == 'plugins.php' || $pagenow == 'network-plugins.php' || $pagenow == 'update-core.php' ||
				$pagenow == 'network-update-core.php')
		) {
			if (!isset($_REQUEST['is-refresh-update'])) {
				$aCacheInfo = $this->getUpdatesFromCache();
				if (!empty($aCacheInfo)) {
					return $this->aResponse;
				}
			}

			$this->requestUpdate();
		}
	}

	/**
	 * @return array[]
	 */
	private function getListOfInstalledPlugins()
	{
		if (!empty($this->aInstalledPlugins)) {
			return $this->aInstalledPlugins;
		}

		if (!function_exists('get_plugins')) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$this->aInstalledPlugins = get_plugins();

		return $this->aInstalledPlugins;
	}

	/**
	 * @return \WP_Theme|null
	 */
	private function getCurrentTheme()
	{
		if ($this->oCurrentThemeVersion !== null) {
			return $this->oCurrentThemeVersion;
		}

		$oMyTheme = wp_get_theme();
		if ($oMyTheme->exists()) {
			$this->oCurrentThemeVersion = false;
		}

		$this->oCurrentThemeVersion = $oMyTheme;

		return $this->oCurrentThemeVersion;
	}

	/**
	 * @param $aPlugin
	 *
	 * @return object
	 */
	private function buildUpdatePluginSkeleton($aPlugin)
	{
		return (object)[
			'slug'         => $aPlugin['slug'],
			'plugin'       => $this->buildPluginPathInfo($aPlugin['slug']),
			'new_version'  => $aPlugin['version'],
			'newVersion'   => $aPlugin['version'],
			'url'          => isset($aPlugin['changelog']) && !empty($aPlugin['changelog']) ?
				$aPlugin['changelog'] :
				wilokeServiceClientGetConfigFile('app')['defaultChangeLogUrl'],
			'package'      => $aPlugin['download'],
			'productType'  => isset($aPlugin['productType']) && !empty($aPlugin['productType']) ?
				$aPlugin['productType'] : 'free',
			'productUrl'   => isset($aPlugin['productUrl']) && !empty($aPlugin['productUrl']) ?
				$aPlugin['productUrl'] : '',
			'requires_php' => $this->phpRequired
		];
	}

	/**
	 * @param $aNewPlugin
	 *
	 * @return mixed|string
	 */
	private function getPreviewURL($aNewPlugin)
	{
		return isset($aNewPlugin['preview']) && !empty($aNewPlugin['preview']) ? $aNewPlugin['preview'] :
			wilokeServiceClientGetConfigFile('app')['serviceURL'];
	}

	/**
	 * @param $pluginID
	 *
	 * @return string
	 */
	private function buildPluginPathInfo($pluginID)
	{
		return $pluginID . '/' . $pluginID . '.php';
	}

	/**
	 * @param $pluginID
	 *
	 * @return string
	 */
	private function updatechangeLogURL($pluginID)
	{
		return wp_nonce_url(self_admin_url('update.php?action=upgrade-plugin&plugin=') .
			$this->buildPluginPathInfo($pluginID),
			'upgrade-plugin_' . $this->buildPluginPathInfo($pluginID));
	}

	/**
	 * @return bool
	 */
	public function reUpdateResponseOfTheme()
	{
		if (!current_user_can('administrator')) {
			return false;
		}
		$this->isFocusGetUpdates = true;
		$this->requestUpdate();
		$this->directlyUpdateTheme();
	}

	/**
	 * @return bool
	 */
	public function reUpdateResponseOfPlugins()
	{
		if (!current_user_can('administrator')) {
			return false;
		}
		$this->isFocusGetUpdates = true;
		$this->requestUpdate();
		$this->directlyUpdatePlugins();
	}

	/**
	 * @param $oTransient
	 *
	 * @return mixed
	 */
	public function updateThemes($oTransient)
	{
		if (General::isWilokeServicePage()) {
			return $oTransient;
		}

		if (empty($this->aTheme)) {
			return $oTransient;
		}

		if (isset($oTransient->checked)) {
			if ($this->oCurrentThemeVersion && version_compare($this->getCurrentTheme()->get('Version'),
					$this->aTheme['version'], '<')
			) {
				$oTheme = [];
				$oTheme['theme'] = $this->aTheme['slug'];
				$oTheme['new_version'] = $this->aTheme['version'];
				$oTheme['package'] = $this->aTheme['download'];
				$oTheme['url']
					= isset($this->aTheme['changelog']) && !empty($this->aTheme['changelog']) ?
					$this->aTheme['changelog'] : $this->changeLogURL;
				$oTransient->response[$this->aTheme['slug']] = $oTheme;
			}
		}

		return $oTransient;
	}

	/**
	 * @param $oTransient
	 *
	 * @return mixed
	 */
	public function updatePlugins($oTransient)
	{
		if (General::isWilokeServicePage()) {
			return $oTransient;
		}

		if (isset($oTransient->checked) || $isDebug = true) {
			// send purchased code
			if (empty($this->aPlugins) || is_wp_error($this->aPlugins)) {
				return $oTransient;
			}

			foreach ($this->aPlugins as $aPlugin) {
				$path = $this->buildPluginPathInfo($aPlugin['slug']);
				if (isset($this->aInstalledPlugins[$path]) &&
					version_compare($this->aInstalledPlugins[$path]['Version'],
						$aPlugin['version'], '<')
				) {
					$oTransient->response[$path] = $this->buildUpdatePluginSkeleton($aPlugin);
				}
			}
			$this->setLastCheckedUpdatePlugins();
		}

		return $oTransient;
	}

	/**
	 * @return bool
	 */
	public function enqueueScripts()
	{
		if (!General::isWilokeServicePage()) {
			return false;
		}

		wp_enqueue_style('style', WILOKESERVICE_CLIENT_SOURCE . 'style.css');
		wp_enqueue_script('updates');
		wp_enqueue_script('updateplugin', WILOKESERVICE_CLIENT_SOURCE . 'updateplugin.js', ['jquery', 'updates'],
			WILOKESERVICE_VERSION, true);
	}

	public function renderHeading()
	{
		?>
        <div class="ui segment">
            <h2 class="ui heading">Thanks for using Wiloke Service!</h2>
            <p>If you have any question or issue while using Wilcity Service, feel free open a ticket on <a
                        href="https://wiloke.com/support/" target="_blank">www.wiloke.com</a></p>
        </div>
		<?php
	}

	public function openUpdateForm()
	{
		if (in_array($this->aResponse['status'], ['error'])) {
			?>
            <div class="ui message negative">
				<?php echo $this->aResponse['msg']; ?>
            </div>
			<?php
		}
		?>
        <div id="wilokeservice-updates-wrapper" class="ui">
		<?php
	}

	private function moveCurrentThemeToFirstPlace($aThemes)
	{
		$aCurrentTheme = [];
		foreach ($aThemes as $order => $aTheme) {
			if ($this->isCurrentTheme($aTheme)) {
				unset($aThemes[$order]);
				$aCurrentTheme = $aTheme;
			}
		}

		if (!empty($aCurrentTheme)) {
			array_unshift($aThemes, $aCurrentTheme);
		}

		return $aThemes;
	}

	public function isCurrentTheme($aTheme)
	{
		if (defined('WILOKE_THEME_SLUG') && WILOKE_THEME_SLUG == $aTheme['slug']) {
			return true;
		}
	}

	public function isInstalledTheme($themeSlug)
	{
		return is_dir(dirname(get_template_directory()) . '/' . $themeSlug);
	}

	public function closeUpdateForm()
	{
		?>
        </div>
		<?php
	}

	public function selectThemes()
	{
		if (!$this->isEnteredToken()) {
			return false;
		}

		$aResponse = wilokeServiceRestRequest()->request(wilokeServiceGetRequest()->setEndpoint('themes'))
			->getResponse();

		?>
        <div id="wilokeservice-update-theme" class="ui segment" style="margin-top: 30px;">
            <h3 class="ui heading">Select Theme</h3>

            <div class="ui message wil-plugin-update-msg hidden"></div>
			<?php if ($aResponse['status'] != 'success') : ?>
                <p class="ui message error positive"><?php echo $aResponse['msg']; ?></p>
			<?php else: ?>
                <div class="ui cards">
					<?php
					$aThemes = $this->moveCurrentThemeToFirstPlace($aResponse['items']);
					foreach ($aThemes as $aTheme) {
						include WILOKESERVICE_CLIENT_VIEWS_DIR . 'theme-item.php';
					}
					?>
                </div>
			<?php endif; ?>
        </div>
		<?php
	}

	/**
	 * @return bool
	 */
	public function showUpPlugins()
	{
		if (!$this->isEnteredToken()) {
			return false;
		}

		if (in_array($this->responseCode, $this->aStatusCodeNoNeedToPrintUpdate)) {
			return false;
		}

		$this->getCurrentTheme();
		$aResponse = ThemeInformation::isWilokeThemeAuthor() ?
			wilokeServiceRestRequest()->request(wilokeServiceGetRequest()
				->setEndpoint('themes/' . ThemeInformation::getThemeSlug() . '/plugins'))
				->getResponse() : ['status' => 'error'];
		?>
        <div id="wilokeservice-update-plugins" class="ui segment">
            <h3 class="ui heading"><?php echo sprintf('%s`s Plugins',
					$this->oCurrentThemeVersion->get('Name')); ?></h3>
            <div class="ui message wil-plugin-update-msg hidden"></div>

			<?php if ($aResponse['status'] == 'error' || empty($aResponse['items'])) : ?>
                <p class="ui message error positive"><?php echo 'Oops! We could not find any plugin'; ?></p>
			<?php else: $this->getListOfInstalledPlugins(); ?>
                <div class="ui cards" style="margin-bottom: 10px;">
					<?php foreach ($aResponse['items'] as $aPlugin) : ?>
						<?php include WILOKESERVICE_CLIENT_VIEWS_DIR . 'plugin-item.php'; ?>
					<?php endforeach; ?>
                </div>
			<?php endif; ?>
        </div>

        <a id="wiloke-refresh-update-btn"
           class="ui button green"
           href="<?php echo $this->generateLink(
			   [
				   'page'              => wilokeServiceClientGetConfigFile('app')['updateSlug'],
				   'is-refresh-update' => 'yes'
			   ]
		   ); ?>">Refresh</a>
		<?php
	}

	/**
	 * @param $plugin
	 */
	public function afterActivatePlugin($plugin)
	{
		$this->afterSwitchTheme('');
	}

	public function testHandleStatistic()
	{
		if (isset($_GET['page']) && $_GET['page'] === wilokeServiceClientGetConfigFile('app')['updateSlug']) {
			$this->afterSwitchTheme('');
		}
	}

	/**
	 * @param $oldThemeName
	 */
	public function afterSwitchTheme($oldThemeName)
	{
		if (!$this->isEnteredToken()) {
			return false;
		}

		$aData = [];
		$oTheme = wp_get_theme();
		$aData['prevThemeName'] = $oldThemeName;
		$aData['themeName'] = ThemeInformation::getThemeSlug();
		$aData['version'] = $oTheme->get('Version');
		$aData['email'] = get_option('admin_email');
		$aData['website'] = home_url('/');

		$status = wilokeServiceRestRequest()
			->request(wilokeServicePostRequest()->setEndpoint('switched-t')->setRequestArgs($aData));

		if ($status) {
			update_option('wiloke_service_statistic', true);
		}
	}

	/**
	 * @return bool
	 */
	public function focusRequestStatistic()
	{
		if (!isset($_REQUEST['page']) ||
			$_REQUEST['page'] !== wilokeServiceClientGetConfigFile('app')['updateSlug']) {
			return false;
		}

		if (!$this->isFocus()) {
			return false;
		}

		if (!current_user_can('administrator') || get_option('wiloke_service_statistic')) {
			return false;
		}

		$this->afterSwitchTheme('');
	}
}
