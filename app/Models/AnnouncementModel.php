<?php


namespace WilokeServiceClient\Models;


class AnnouncementModel
{
	private static $key            = 'wiloke_announcements';
	private static $aAnnouncements = [];

	public static function update($category, $key, $val)
	{
		self::getAll();
		if (!isset(self::$aAnnouncements[$category])) {
			self::$aAnnouncements[$category] = [
				$key => $val
			];
		} else {
			self::$aAnnouncements[$category][$key] = $val;
		}
		return update_option(self::$key, self::$aAnnouncements);
	}

	public static function delete($category, $key)
	{
		self::getAll();

		if (!isset(self::$aAnnouncements[$category])) {
			return false;
		}
		unset(self::$aAnnouncements[$category][$key]);

		return update_option(self::$key, self::$aAnnouncements);
	}

	public static function getAll($isFocus = false)
	{
		if (!empty(self::$aAnnouncements) && !$isFocus) {
			return self::$aAnnouncements;
		}

		self::$aAnnouncements = get_option(self::$key);

		return empty(self::$aAnnouncements) ? [] : self::$aAnnouncements;
	}

	public static function get($category, $key, $default = '', $isFocus = false)
	{
		self::getAll($isFocus);
		if (!isset(self::$aAnnouncements[$category])) {
			return $default;
		}

		return isset(self::$aAnnouncements[$category][$key]) ? self::$aAnnouncements[$category][$key] : $default;
	}
}