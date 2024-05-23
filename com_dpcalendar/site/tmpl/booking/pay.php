<?php
use Joomla\CMS\Plugin\PluginHelper;
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2014 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */
defined('_JEXEC') or die();

$this->dpdocument->addStyle($this->params->get('booking_custom_css', ''));

PluginHelper::importPlugin('dpcalendarpay');

$button = $this->app->triggerEvent('onDPPaymentNew', [$this->booking]);
foreach ($button as $b) {
	echo $b;
}
