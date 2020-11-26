<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2016 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */
defined('_JEXEC') or die();

$price = $displayData['price'];
if (!$price) {
	$price = '0';
}

$separator = DPCalendarHelper::getComponentParameter('currency_separator', '.');
if (key_exists('separator', $displayData) && $displayData['separator']) {
	$separator = $displayData['separator'];
}

$thousandSeparator = DPCalendarHelper::getComponentParameter('currency_thousands_separator', "'");
if (key_exists('thousands_separator', $displayData) && $displayData['thousands_separator']) {
	$thousandSeparator = $displayData['thousands_separator'];
}

$price = number_format(trim($price), 2, $separator, $thousandSeparator);

$currency = DPCalendarHelper::getComponentParameter('currency_symbol', '$');
if (key_exists('currency', $displayData) && $displayData['currency']) {
	$currency = $displayData['currency'];
}

if ($currency == '$' || $currency == '£') {
	echo htmlentities($currency . ' ' . $price);

	return;
}

echo htmlentities($price . ' ' . $currency);
