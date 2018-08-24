<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2018 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */
defined('_JEXEC') or die();

$price = $displayData['price'];
if (!$price) {
	$price = '0';
}

$price = number_format($price, 2);

$currency = DPCalendarHelper::getComponentParameter('currency_symbol', '$');

if (key_exists('currency', $displayData) && $displayData['currency']) {
	$currency = $displayData['currency'];
}

if ($currency == '$') {
	echo htmlentities($currency . ' ' . $price);

	return;
}

echo htmlentities($price . ' ' . $currency);
