<?php


namespace WilokeServiceClient\Controllers\Download;


use WilokeServiceClient\Helpers\Download;

class DownloadPlugin implements IDownload
{
	private $itemName;

	public function getDownloadUrl()
	{
		return Download::downloadPluginUrl($this->itemName);
	}

	public function getItemFilePath()
	{
		return $this->getExtractTo() . $this->parsePluginName();
	}

	public function parsePluginName()
	{
		$aParsed = explode('/', trim($this->itemName));
		return $aParsed[0];
	}

	public function getExtractTo()
	{
		return dirname(WILOKESERVICE_CLIENT_DIR);
	}

	public function setItemName($itemName)
	{
		$this->itemName = $itemName;

		return $this;
	}
}