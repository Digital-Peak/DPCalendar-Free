<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2016 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

defined('_JEXEC') or die();

use DigitalPeak\Component\DPCalendar\Administrator\Helper\DPCalendarHelper;

$price = $displayData['price'];
if (!$price) {
	$price = '0';
}

$separator = DPCalendarHelper::getComponentParameter('currency_separator', '.');
if (array_key_exists('separator', $displayData) && $displayData['separator']) {
	$separator = $displayData['separator'];
}

$thousandSeparator = DPCalendarHelper::getComponentParameter('currency_thousands_separator', "'");
if (array_key_exists('thousands_separator', $displayData) && $displayData['thousands_separator']) {
	$thousandSeparator = $displayData['thousands_separator'];
}

$price = number_format((float)trim((string) $price), 2, $separator, $thousandSeparator);

$currency = DPCalendarHelper::getComponentParameter('currency_symbol', '$');
if (array_key_exists('currency', $displayData) && $displayData['currency']) {
	$currency = $displayData['currency'];
}

if ($currency == '$' || $currency == '£') {
	echo htmlentities($currency . ' ' . $price, ENT_COMPAT, 'UTF-8');

	return;
}

echo htmlentities($price . ' ' . $currency, ENT_COMPAT, 'UTF-8');
