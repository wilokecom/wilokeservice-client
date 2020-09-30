<?php


namespace WilokeServiceClient\Controllers\Download;


use WilokeServiceClient\Helpers\Download;
use WilokeServiceClient\Helpers\Option;

class AbstractDownload
{
	private $downloadUrl;
	private $oDownloadResponse;

	/**
	 * @var IDownload $oItem
	 */
	private $oItem;

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

	/**
	 * @param $package
	 * @param bool $isLive
	 * @return bool|true|\WP_Error
	 */
	private function processUnzipFile($package, $isLive = true)
	{
		WP_Filesystem();
		$status = unzip_file($package, $this->oItem->getExtractTo());

		if ($isLive) {
			@unlink($package);
		}

		return $status;
	}

	private function processDownload()
	{
		$this->oDownloadResponse = download_url($this->oItem->getDownloadUrl());

		if (empty($this->oDownloadResponse) || is_wp_error($this->oDownloadResponse)) {
			return [
				'status' => 'error',
				'msg'    => $this->oDownloadResponse->get_error_message()
			];
		}

		return [
			'status' => 'success',
			'msg'    => 'The file has been downloaded'
		];
	}

	private function unzipFile()
	{
		$extractedFile = $this->processUnzipFile($this->oDownloadResponse, false);

		if ($extractedFile !== true) {
			return [
				'status' => 'error',
				'msg'    => $extractedFile->get_error_message()
			];
		}

		return [
			'status' => 'success',
			'msg'    => 'Congrats, The plugin has been downloaded successfully, please click on Activate button to active this plugin'
		];
	}

	public function install(IDownload $oDownloadTarget)
	{
		$this->oItem = $oDownloadTarget;

		$aStatus = $this->processDownload();
		if ($aStatus['status'] === 'error') {
			return $aStatus;
		}

		return $this->unzipFile();
	}
}