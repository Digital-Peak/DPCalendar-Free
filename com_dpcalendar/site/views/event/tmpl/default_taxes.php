<?php
/**
 * @package   DPCalendar
 * @author    Digital Peak http://www.digital-peak.com
 * @copyright Copyright (C) 2007 - 2020 Digital Peak. All rights reserved.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */
defined('_JEXEC') or die();

if (!$this->taxRate) {
	return;
}

$text = sprintf(
	$this->translate('COM_DPCALENDAR_VIEW_EVENT_TAXES_' . ($this->taxRate->inclusive ? 'INCLUSIVE' : 'EXCLUSIVE') . '_TEXT'),
	$this->taxRate->rate,
	$this->translate('COM_DPCALENDAR_COUNTRY_' . $this->country->short_code)
);
?>
<div class="com-dpcalendar-event__taxes dp-info-box"><?php echo $text ?></div>
