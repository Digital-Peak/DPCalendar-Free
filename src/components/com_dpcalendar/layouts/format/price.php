<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2017 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */
defined('_JEXEC') or die();

$price = $displayData['price'];
if (!$price) {
	return;
}

$currency = DPCalendarHelper::getComponentParameter('currency_symbol', '$');

if (key_exists('currency', $displayData) && $displayData['currency']) {
	$currency = $displayData['currency'];
}

echo htmlentities($price . ' ' . $currency);
