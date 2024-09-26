<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2016 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

use Joomla\CMS\Factory;

defined('_JEXEC') or die();

$price = $displayData['price'];
if (!$price) {
	$price = '0';
}

$currency = Factory::getApplication()->bootComponent('dpcalendar')->getMVCFactory()->createModel('Currency', 'Administrator')->getActualCurrency();

$price = number_format((float)trim((string) $price), 2, $currency->separator, $currency->thousands_separator);

if ($currency->symbol === '$' || $currency->symbol === '£') {
	echo htmlentities($currency->symbol . ' ' . $price, ENT_COMPAT, 'UTF-8');

	return;
}

echo htmlentities($price . ' ' . $currency->symbol, ENT_COMPAT, 'UTF-8');
