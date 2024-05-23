<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2021 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

defined('_JEXEC') or die();

use DigitalPeak\Component\DPCalendar\Administrator\Helper\DPCalendarHelper;

if (!$this->params->get('event_show_calendar', '1')) {
	return;
}

$calendar     = \Joomla\CMS\Factory::getApplication()->bootComponent('dpcalendar')->getMVCFactory()->createModel('Calendar', 'Administrator')->getCalendar($this->event->catid);
$sd           = $this->dateHelper->getDate($this->event->start_date, $this->event->all_day);
$calendarLink = $this->router->getCalendarRoute($this->event->catid);
if ($calendarLink && $this->params->get('event_show_calendar', '1') == '2') {
	$calendarLink .= '#year=' . $sd->format('Y', true) . '&month=' . $sd->format('m', true) . '&day=' . $sd->format('d', true);
}
?>
<dl class="dp-description dp-information__calendar">
	<dt class="dp-description__label"><?php echo $this->translate('COM_DPCALENDAR_CALENDAR'); ?></dt>
	<dd class="dp-description__description">
		<?php if ($calendarLink && $calendar instanceof \DigitalPeak\Component\DPCalendar\Administrator\Calendar\CalendarInterface) { ?>
			<a href="<?php echo $calendarLink; ?>" class="dp-link"><?php echo $calendar->getTitle(); ?></a>
		<?php } else { ?>
			<?php echo $calendar instanceof \DigitalPeak\Component\DPCalendar\Administrator\Calendar\CalendarInterface ? $calendar->getTitle() : $this->event->catid; ?>
		<?php } ?>
	</dd>
</dl>
