<?php

namespace WilokeServiceClient\Controllers;

use WilokeService\RestApi\GetAPI;
use WilokeServiceClient\Helpers\App;
use WilokeServiceClient\Helpers\GetSettings;
use WilokeServiceClient\Helpers\ThemeInformation;
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
//		add_action('admin_init', [$this, 'getUpdates'], 1);

//		add_filter('http_request_args', [$this, 'updateCheck'], 5, 2);
		add_action('wilokeservice-clients/theme-updates', [$this, 'openUpdateForm'], 1);
//		add_action('wilokeservice-clients/theme-updates', [$this, 'selectThemes']);
		add_action('wilokeservice-clients/theme-updates', [$this, 'selectThemes']);
		add_action('wilokeservice-clients/theme-updates', [$this, 'showUpPlugins'], 20);
		add_action('wilokeservice-clients/theme-updates', [$this, 'closeUpdateForm'], 30);
		add_action('admin_enqueue_scripts', [$this, 'enqueueScripts']);

		add_filter('pre_set_site_transient_update_plugins', [$this, 'updatePlugins']);
		add_filter('pre_set_transient_update_plugins', [$this, 'updatePlugins']);
//
//		add_filter('pre_set_site_transient_update_themes', [$this, 'updateThemes'], 1, 99999);
//		add_filter('pre_set_transient_update_themes', [$this, 'updateThemes'], 1, 99999);

		add_action('admin_init', [$this, 'checkUpdatePluginDirectly'], 10);
		add_filter('http_request_args', [$this, 'addBearTokenToHeaderDownload'], 10, 2);

//		add_action('wp_ajax_wiloke_reupdate_response_of_theme', [$this, 'reUpdateResponseOfTheme']);
//		add_action('wp_ajax_wiloke_reupdate_response_of_plugins', [$this, 'reUpdateResponseOfPlugins']);
//		add_action('admin_init', [$this, 'clearUpdatePluginTransients'], 1);

		add_action('after_switch_theme', [$this, 'afterSwitchTheme']);
		add_action('activated_plugin', [$this, 'afterActivatePlugin']);
		add_action('admin_init', [$this, 'focusRequestStatistic']);
		//        add_action('admin_init', [$this, 'testHandleStatistic']);
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
				$pagenow == 'network-update-core.php') && !General::isWilcityServicePage()
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

	/**
	 * @return mixed
	 */
	private function _getUpdates()
	{
		if (!$this->isFocus()) {
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
		}

		$this->aResponse = RestAPI::get('themes/' . ThemeInformation::getThemeSlug());
		$this->responseCode = isset($this->aResponse['code']) ? $this->aResponse['code'] : 'OKE';
		if ($this->aResponse['status'] == 'success') {
			$aRawPlugins = $this->aResponse['aPlugins'];
			foreach ($aRawPlugins as $aPlugin) {
				$this->aPlugins[$this->buildPluginPathInfo($aPlugin['slug'])] = $aPlugin;
			}
			$this->aTheme = $this->aResponse['aTheme'];
			set_transient($this->cacheUpdateKeys, $this->aResponse, $this->saveUpdateInfoIn);
		} else {
			$this->aPlugins = false;
			$this->aTheme = false;
			$this->aResponse['status'] = 'error';
			$this->errMgs = isset($this->aResponse['msg']) ? $this->aResponse['msg'] : '';
			set_transient($this->cacheUpdateKeys, $this->aResponse, $this->saveUpdateInfoIn * 20);
		}
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
			//            }

			set_site_transient('update_themes', $oListThemesInfo);
		}
	}

	/**
	 * @return bool
	 */
	public function checkUpdatePluginDirectly()
	{
		if (!General::isWilcityServicePage() || !$this->isNeededToRecheckUpdatePlugins()) {
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
		if (General::isWilcityServicePage() ||
			($pagenow == 'plugins.php' || $pagenow == 'network-plugins.php' || $pagenow == 'update-core.php' ||
				$pagenow == 'network-update-core.php')
		) {
			$this->_getUpdates();
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
			'url'          => isset($aPlugin['changelog']) && !empty($aPlugin['changelog']) ? $aPlugin['changelog'] :
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
		$this->_getUpdates();
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
		$this->_getUpdates();
		$this->directlyUpdatePlugins();
	}

	/**
	 * @param $oTransient
	 *
	 * @return mixed
	 */
	public function updateThemes($oTransient)
	{
		if (General::isWilcityServicePage()) {
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
		if (General::isWilcityServicePage()) {
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
		if (!General::isWilcityServicePage()) {
			return false;
		}

		wp_enqueue_style('style', WILOKESERVICE_CLIENT_SOURCE . 'style.css');
		wp_enqueue_script('updates');
		wp_enqueue_script('updateplugin', WILOKESERVICE_CLIENT_SOURCE . 'updateplugin.js', ['jquery', 'updates'],
			WILOKESERVICE_VERSION, true);
	}

	public function openUpdateForm()
	{
		switch ($this->responseCode) {
			case 'IP_BLOCKED':
				?>
                <div class="ui message negative">
					<?php echo $this->errMgs; ?>
                </div>
				<?php
				break;
			case 'PurchasedCodeExpired':
				?>
                <div class="ui message negative">
                    The Support Plan was expired.
                    <a href="<?php echo esc_url(wilokeServiceClientGetConfigFile('app')['renewSupportURL']); ?>"
                       target="_blank">Renew it now</a>
                </div>
				<?php
				break;
			case 'INVALID_TOKEN':
				?>
                <div class="ui message negative">
                    Invalid Token. Please log into <a
                            href="<?php echo esc_url(wilokeServiceClientGetConfigFile('app')['serviceURL']); ?>"
                            target="_blank">Wilcity
                        Service</a> to renew one.
                </div>
				<?php
				break;
			case 'CLIENT_WEBSITE_IS_INVALID':
				?>
                <div class="ui message negative">
                    This website is not listed in Website Urls of this token. Please log into <a
                            href="<?php echo esc_url(wilokeServiceClientGetConfigFile('app')['serviceURL']); ?>"
                            target="_blank">Wiloke Service -> Theme Information</a>
                    and check it again.
                </div>
				<?php
				break;
		}
		?>
        <div id="wilokeservice-updates-wrapper" class="ui <?php echo $this->responseCode == 'PurchasedCodeExpired' ?
		'disable' : 'oke'; ?>">
		<?php
	}

	/**
	 * @param $aNewPlugin
	 * @param $aCurrentPluginInfo
	 */
	private function renderPluginButtons($aNewPlugin, $aCurrentPluginInfo)
	{
		$tgmpaUrl = admin_url('themes.php?page=tgmpa-install-plugins&plugin_status=install');
		?>
        <div class="extra content">
			<?php wp_nonce_field('wiloke-service-nonce', 'wiloke-service-nonce-value'); ?>
            <div class="ui two buttons wil-button-wrapper"
                 data-slug="<?php echo esc_attr($aNewPlugin['slug']); ?>"
                 data-plugin="<?php echo esc_attr($this->buildPluginPathInfo($aNewPlugin['slug'])); ?>">
				<?php if (!$aCurrentPluginInfo) : ?>
                    <div class="ui basic green button">
                        <a href="<?php echo esc_url($tgmpaUrl); ?>"
                           class="wil-install-plugin wilokeservice-plugin"
                           data-slug="<?php echo esc_attr($aNewPlugin['slug']); ?>"
                           data-action="wiloke_download_plugin"
                           data-plugin="<?php echo esc_attr($this->buildPluginPathInfo($aNewPlugin['slug'])); ?>"
                           target="_blank">Install</a>
                    </div>
				<?php elseif (General::isNewVersion($aNewPlugin['version'], $aCurrentPluginInfo['Version'])): ?>
                    <div class="ui basic green button">
                        <a class="wil-update-plugin"
                           href="<?php echo esc_url($this->updatechangeLogURL($aNewPlugin['slug'])); ?>">Update</a>
                    </div>
				<?php else: ?>
					<?php if (!is_plugin_active($this->buildPluginPathInfo($aNewPlugin['slug']))) : ?>
                        <div class="ui basic green button">
                            <a href="<?php echo esc_url($tgmpaUrl); ?>"
                               class="wil-active-plugin wilokeservice-plugin"
                               data-action="wiloke_activate_plugin"
                               data-slug="<?php echo esc_attr($aNewPlugin['slug']); ?>"
                               data-plugin="<?php echo esc_attr($this->buildPluginPathInfo($aNewPlugin['slug'])); ?>"
                               target="_blank">Activate</a>
                        </div>
					<?php else: ?>
                        <div class="ui basic green button">
                            <a href="<?php echo esc_url($tgmpaUrl); ?>"
                               class="wil-deactivate-plugin wilokeservice-plugin"
                               data-action="wiloke_deactivate_plugin"
                               data-slug="<?php echo esc_attr($aNewPlugin['slug']); ?>"
                               data-plugin="<?php echo esc_attr($this->buildPluginPathInfo($aNewPlugin['slug'])); ?>"
                               target="_blank">Deactivate</a>
                        </div>
					<?php endif; ?>
                    <div class="ui basic red button">
                        <a target="_blank"
                           href="<?php echo esc_url($this->getPreviewURL($aNewPlugin)); ?>">Changelog</a>
                    </div>
				<?php endif; ?>
            </div>
        </div>
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
		return is_file(get_template_directory() . '/' . $themeSlug);
	}

	public function closeUpdateForm()
	{
		?>
        </div>
		<?php
	}

	public function selectThemes()
	{
		$aResponse = wilcityServiceRestRequest()->request(wilcityServiceGetRequest()->setEndpoint('themes'))
			->getResponse();
		?>
        <div id="wilokeservice-update-theme" class="ui segment" style="margin-top: 30px;">
            <h3 class="ui heading">Select Theme</h3>

            <div class="ui message wil-plugin-update-msg hidden"></div>
			<?php if ($aResponse['status'] != 'success') : ?>
                <p class="ui message error positive"><?php echo 'Oops! We could no themes.'; ?></p>
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
		if (in_array($this->responseCode, $this->aStatusCodeNoNeedToPrintUpdate)) {
			return false;
		}

		$this->getCurrentTheme();
//		$aResponse = defined('WILOKE_THEME_SLUG') ? wilcityServiceRestRequest()->request(wilcityServiceGetRequest()
//			->setEndpoint('themes/hsblog-blog-magazine-news-wordpress-theme-and-ios-android-app/plugins'))
//			->getResponse() : [];

		$aResponse = wilcityServiceRestRequest()->request(wilcityServiceGetRequest()
			->setEndpoint('themes/hsblog-blog-magazine-news-wordpress-theme-and-ios-android-app/plugins'))
			->getResponse();
		?>
        <div id="wilokeservice-update-plugins" class="ui segment">
            <h3 class="ui heading"><?php echo sprintf('%s`s Plugins', $this->oCurrentThemeVersion->get('Name')); ?></h3>
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

        <a class="ui button green"
           href="<?php echo admin_url(
			   [
				   'page'              => wilokeServiceClientGetConfigFile('app')['updateSlug'],
				   'is-refresh-update' => 'yes'
			   ],
			   admin_url('admin.php')
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
		$aData = [];
		$oTheme = wp_get_theme();
		$aData['prevThemeName'] = $oldThemeName;
		$aData['themeName'] = ThemeInformation::getThemeSlug();
		$aData['version'] = $oTheme->get('Version');
		$aData['email'] = get_option('admin_email');
		$aData['website'] = home_url('/');

		$status = RestAPI::post('switched-t', $aData);

		if ($status) {
			update_option('wiloke_service_statistic', true);
		}
	}

	/**
	 * @return bool
	 */
	public function focusRequestStatistic()
	{
		if (!isset($_REQUEST['page']) || $_REQUEST['page'] !== wilokeServiceClientGetConfigFile('app')['updateSlug']) {
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
