<?php

namespace WilokeServiceClient\Helpers;

/**
 * Class ThemeInformation
 * @package WilokeServiceClient\Helpers
 */
class ThemeInformation
{
	public static $oTheme;

	public static function getTheme()
	{
		if (empty(self::$oTheme)) {
			self::$oTheme = wp_get_theme();
		}
	}

	/**
	 * @return array|false|string
	 */
	public static function getThemeSlug()
	{
		self::getTheme();

		$stylesheet = self::$oTheme->get_stylesheet();
		if (strpos($stylesheet, 'child') !== false) {
			$stylesheet = str_replace(['childtheme', 'child-theme', 'child', '-'], ['', '', '', ''], $stylesheet);
		}

		return $stylesheet;
	}

	public static function isWilokeThemeAuthor()
	{
		self::getTheme();

		$author = self::$oTheme->get('Author');

		return strpos(strtolower($author), 'wiloke') !== false;
	}
}
