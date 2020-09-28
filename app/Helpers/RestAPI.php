<?php

namespace WilokeServiceClient\Helpers;


/**
 * Class RestApi
 * @package WilokeServiceClient\Helpers
 */
class RestAPI
{
	protected $endpoint;
	protected $aResponse;

	/**
	 * @return string
	 */
	public function getUpdateServiceURL()
	{
		return wilokeServiceClientGetConfigFile('app')['serviceURL'];
	}

	/**
	 * @param $themeName
	 *
	 * @return string
	 */
	public function buildThemeEndpoint($themeName)
	{
		return 'themes/' . strtolower(trim($themeName));
	}

	/**
	 * @return string
	 */
	public static function getBearToken()
	{
		$token = GetSettings::getOptionField('secret_token');

		return 'Bearer ' . $token;
	}

	/**
	 * @param $endpoint
	 *
	 * @return string
	 */
	protected function buildEndpoint($endpoint)
	{
		return wilokeServiceClientGetConfigFile('app')['baseURL'] . ltrim($endpoint, '/');
	}

	/**
	 * @param $response
	 *
	 * @return array
	 */
	private function verifyFirewallIssue($response)
	{
		if (strpos($response, 'FireWall') !== false) {
			$response = strip_tags($response);
			preg_match('/((\d+\.\d+){3,})/m', $response, $aMatches);

			return [
				'status' => 'error',
				'msg'    => sprintf('Your IP address %s has been blocked by Wilcity Service FireWall. Please contact Wilcity Support on forum address to report this issue',
					$aMatches[1]),
				'code'   => 'IP_BLOCKED'
			];
		}

		return [
			'status' => 'success'
		];
	}

	protected function getDefaultCurlOptions()
	{
		return [
			CURLOPT_URL            => $this->buildEndpoint($this->endpoint),
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_REFERER        => home_url('/'),
			CURLOPT_ENCODING       => '',
			CURLOPT_MAXREDIRS      => 10,
			CURLOPT_TIMEOUT        => 30,
			CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
			CURLOPT_HTTPHEADER     => [
				"authorization: Bearer " . GetSettings::getOptionField('secret_token'),
				"cache-control: no-cache",
				'Content-Type:application/json'
			]
		];
	}

	public function getResponse()
	{
		return $this->aResponse;
	}

	public function isError()
	{
		return $this->aResponse['status'] == 'error';
	}

	/**
	 * @param IRestAPI $oRequestMethod
	 * @return $this|array|string[]
	 */
	public function request(IRestAPI $oRequestMethod)
	{
		$token = GetSettings::getOptionField('secret_token');
		if (empty($token)) {
			return [
				'status' => 'error',
				'msg'    => 'The Secret Token is required'
			];
		}

		$curl = curl_init();
		curl_setopt_array($curl, $oRequestMethod->buildCurlBodyOptions());
		$response = curl_exec($curl);
		$error = curl_error($curl);
		curl_close($curl);

		if ($error) {
			$this->aResponse = [
				'status' => 'error',
				'msg'    => $error
			];
		} else {
			$aVerifyFirewallIssue = $this->verifyFirewallIssue($response);
			if ($aVerifyFirewallIssue['status'] == 'error') {
				return $aVerifyFirewallIssue;
			}

			$aResponse = json_decode($response, true);
			if ($aResponse['status'] == 'error') {
				$this->aResponse = $aResponse;
			} else {
				$this->aResponse = wp_parse_args(
					$aResponse,
					[
						'status' => 'success'
					]
				);
			}
		}

		return $this;
	}
}
