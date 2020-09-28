<?php


namespace WilokeServiceClient\Helpers;

class PostAPI extends RestAPI implements IRestAPI
{
	/**
	 * @var $aOptions
	 */
	private $aArgs;

	public function setEndpoint($endpoint)
	{
		$this->endpoint = $endpoint;

		return $this;
	}

	public function setRequestArgs($aArgs)
	{
		$this->aArgs = $aArgs;

		return $this;
	}

	public function buildCurlBodyOptions()
	{
		return $this->getDefaultCurlOptions() +
			[
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_REFERER        => home_url('/'),
				CURLOPT_ENCODING       => '',
				CURLOPT_MAXREDIRS      => 10,
				CURLOPT_TIMEOUT        => 30,
				CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
				CURLOPT_POSTFIELDS     => $this->aArgs,
				CURLOPT_POST           => '1'
			];
	}
}