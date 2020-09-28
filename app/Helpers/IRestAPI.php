<?php


namespace WilokeServiceClient\Helpers;


interface IRestAPI
{
	public function setEndpoint($endpoint);

	public function setRequestArgs($aOptions);

	public function getResponse();

	public function buildCurlBodyOptions();
}