<?php


namespace WilokeServiceClient\Models;


class LicenseModel
{
	private static $nextBillingDateKey = 'wiloke_next_billing_date';

	public static function update($gmtTimestamp)
	{
		update_option(self::$nextBillingDateKey, $gmtTimestamp);
	}

	public static function delete()
	{
		delete_option(self::$nextBillingDateKey);
	}

	public static function get()
	{
		$value = get_option(self::$nextBillingDateKey);
		return empty($value) ? 0 : abs($value);
	}
}