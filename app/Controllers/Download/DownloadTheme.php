<?php


namespace WilokeServiceClient\Controllers\Download;


use WilokeServiceClient\Helpers\Download;

class DownloadTheme implements IDownload
{
	private $itemName;

	public function getDownloadUrl()
	{
		return Download::downloadThemeUrl($this->itemName);
	}

	public function getItemFilePath()
	{
		return $this->getExtractTo() . $this->itemName;
	}

	public function getExtractTo()
	{
		return dirname(get_template_directory()) . '/';
	}

	public function setItemName($itemName)
	{
		$this->itemName = $itemName;

		return $this;
	}
}