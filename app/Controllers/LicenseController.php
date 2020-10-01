<?php


namespace WilokeServiceClient\Controllers;


use WilokeServiceClient\Models\AnnouncementModel;
use WilokeServiceClient\Models\LicenseModel;

class LicenseController
{
	public function __construct()
	{
		add_action(WILOKESERVICE_PREFIX . 'daily_event', [$this, 'verify']);
		add_action('admin_init', [$this, 'verify']);
		add_action('wiloke/wilcityservice-client/app/RegisterMenu/RegisterWilcityServiceMenu/savedConfiguration',
			[$this, 'verify']);
	}

	public function focusVerify()
	{
		if (isset($_REQUEST['is-refresh-update']) && $_REQUEST['is-refresh-update'] == 'yes') {
			$this->verify();
		}
	}

	public function verify()
	{
		$aResponse = wilokeServiceRestRequest()->request(wilokeServiceGetRequest()->setEndpoint('verify-license'))
			->getResponse();

		AnnouncementModel::delete('error', 'license_expiry');
		AnnouncementModel::delete('warning', 'license_warning');

		if ($aResponse['status'] === 'error') {
			AnnouncementModel::update('error', 'license_expiry', $aResponse['msg']);
		} elseif (isset($aResponse['codeStatus']) && $aResponse['codeStatus'] !==
			'OK') {
			AnnouncementModel::update('warning', 'license_warning', $aResponse['msg']);
		}

		if (isset($aResponse['nextBillingDate'])) {
			LicenseModel::update($aResponse['nextBillingDate']);
		} else {
			LicenseModel::delete();
		}
	}
}