<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2018 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */

defined('_JEXEC') or die();

class DPCalendarHelperFilter
{
	/**
	 * To Slug
	 *
	 * @param   string  $value  Value to turn to slug
	 *
	 * @return mixed|string
	 */
	public static function toSlug($value)
	{
		// Remove any '-' from the string they will be used as concatonater
		$value = str_replace('-', ' ', $value);

		// Convert to ascii characters
		$value = self::toASCII($value);

		// Lowercase and trim
		$value = trim(strtolower($value));

		// Remove any duplicate whitespace, and ensure all characters are alphanumeric
		$value = preg_replace(array('/\s+/', '/[^A-Za-z0-9\-]/'), array('-', ''), $value);

		// Limit length
		if (strlen($value) > 100)
		{
			$value = substr($value, 0, 100);
		}

		return $value;
	}

	/**
	 * To ASCII
	 *
	 * @param   string  $value  Value to turn to ASCII
	 *
	 * @return mixed|string
	 */
	public static function toASCII($value)
	{
		$string = htmlentities(utf8_decode($value));
		$string = preg_replace(
			array('/&szlig;/', '/&(..)lig;/', '/&([aouAOU])uml;/', '/&(.)[^;]*;/'),
			array('ss', "$1", "$1" . 'e', "$1"),
			$string
		);

		return $string;
	}
}
