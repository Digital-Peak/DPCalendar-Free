<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2021 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

defined('_JEXEC') or die();

use DPCalendar\Helper\DPCalendarHelper;

if (!$this->params->get('event_show_calendar', '1')) {
	return;
}

$calendar     = DPCalendarHelper::getCalendar($this->event->catid);
$sd           = $this->dateHelper->getDate($this->event->start_date, $this->event->all_day);
$calendarLink = $this->router->getCalendarRoute($this->event->catid);
if ($calendarLink && $this->params->get('event_show_calendar', '1') == '2') {
	$calendarLink .= '#year=' . $sd->format('Y', true) . '&month=' . $sd->format('m', true) . '&day=' . $sd->format('d', true);
}
?>
<dl class="dp-description dp-information__calendar">
	<dt class="dp-description__label"><?php echo $this->translate('COM_DPCALENDAR_CALENDAR'); ?></dt>
	<dd class="dp-description__description">
		<?php if ($calendarLink && $calendar) { ?>
			<a href="<?php echo $calendarLink; ?>" class="dp-link"><?php echo $calendar->title; ?></a>
		<?php } else { ?>
			<?php echo $calendar ? $calendar->title : $this->event->catid; ?>
		<?php } ?>
	</dd>
</dl>
