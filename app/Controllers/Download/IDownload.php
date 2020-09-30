<?php


namespace WilokeServiceClient\Controllers\Download;


interface IDownload
{
	/**
	 * Verify Nonce
	 * @return mixed
	 */

	public function getExtractTo();

	public function getItemFilePath();

	/**
	 * Set Plugin Name or Theme name
	 *
	 * @param $itemName
	 * @return mixed
	 */
	public function setItemName($itemName);

	public function getDownloadUrl();
}