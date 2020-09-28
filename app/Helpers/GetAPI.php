<?php


namespace WilokeServiceClient\Helpers;


class GetAPI extends RestAPI implements IRestAPI
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
		$endpoint = home_url('/');
		if (!empty($this->aArgs)) {
			$endpoint = add_query_arg(
				$this->aArgs,
				$this->buildEndpoint($this->endpoint)
			);
		}

		return $this->getDefaultCurlOptions() + [
				CURLOPT_URL            => $endpoint,
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_ENCODING       => '',
				CURLOPT_MAXREDIRS      => 10,
				CURLOPT_TIMEOUT        => 30,
				CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
				CURLOPT_CUSTOMREQUEST  => 'GET'
			];
	}
}