<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2019 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
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

$price = number_format(trim($price), 2, $separator, "'");

$currency = DPCalendarHelper::getComponentParameter('currency_symbol', '$');

if (key_exists('currency', $displayData) && $displayData['currency']) {
	$currency = $displayData['currency'];
}

if ($currency == '$') {
	echo htmlentities($currency . ' ' . $price);

	return;
}

echo htmlentities($price . ' ' . $currency);
