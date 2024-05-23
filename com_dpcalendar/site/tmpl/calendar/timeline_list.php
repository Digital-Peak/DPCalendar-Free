<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2021 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

defined('_JEXEC') or die();

if ($this->params->get('show_selection', 1) == 2) {
	return;
}

use DigitalPeak\Component\DPCalendar\Administrator\Calendar\ExternalCalendarInterface;
use DigitalPeak\Component\DPCalendar\Administrator\Helper\DPCalendarHelper;
use Joomla\Registry\Registry;
use Joomla\CMS\Uri\Uri;
?>
<div class="com-dpcalendar-calendar-timeline__list com-dpcalendar-calendar-timeline__list_<?php echo $this->params->get('show_selection', 1) == 3 ? '' : 'hidden'; ?>">
	<?php if ((is_countable($this->doNotListCalendars) ? count($this->doNotListCalendars) : 0) >= 10) { ?>
		<div class="com-dpcalendar-calendar-timeline__list-toggle">
			<input type="checkbox" id="calendars-toggle" class="dp-input dp-input-checkbox com-dpcalendar-calendar-timeline__list-toggle-input" checked>
			<label for="calendars-toggle" class="dp-input-label"><?php echo $this->translate('COM_DPCALENDAR_VIEW_CALENDAR_TOGGLE'); ?></label>
		</div>
	<?php } ?>
	<div class="com-dpcalendar-calendar-timeline__calendars">
		<?php foreach ($this->doNotListCalendars as $calendar) { ?>
			<?php $style = 'background-color: #' . $calendar->getColor() . ';'; ?>
			<?php $style .= 'border-color: #' . $calendar->getColor() . ';'; ?>
			<?php $style .= 'color: #' . DPCalendarHelper::getOppositeBWColor($calendar->getColor()); ?>
			<?php $icalRoute = $this->router->getCalendarIcalRoute($calendar->getId()); ?>
			<div class="dp-calendar">
				<label for="cal-<?php echo $calendar->getId(); ?>" class="dp-input-label dp-calendar__label">
					<input type="checkbox" name="cal-<?php echo $calendar->getId(); ?>" value="<?php echo $calendar->getId(); ?>"
						   id="cal-<?php echo $calendar->getId(); ?>" class="dp-input dp-input-checkbox dp-calendar__input" style="<?php echo $style; ?>">
					<div class="dp-calendar__title">
						<?php echo str_pad(' ' . $calendar->getTitle(), strlen(' ' . $calendar->getTitle()) + $calendar->level - 1, '-', STR_PAD_LEFT); ?>
					</div>
					<div class="dp-calendar__event-text"><?php echo $calendar->event->afterDisplayTitle; ?></div>
				</label>
				<div class="dp-calendar__links">
					<?php if ((!empty($calendar->getIcalUrl()) || !$calendar instanceof ExternalCalendarInterface) && $this->params->get('show_export_links', 1)) { ?>
						<a href="<?php echo str_replace(['http://', 'https://'], 'webcal://', (string) $icalRoute); ?>"
						   class="dp-link dp-link-subscribe">
							[<?php echo $this->translate('COM_DPCALENDAR_VIEW_CALENDAR_TOOLBAR_SUBSCRIBE'); ?>]
						</a>
						<a href="<?php echo $icalRoute; ?>" class="dp-link">
							[<?php echo $this->translate('COM_DPCALENDAR_VIEW_CALENDAR_TOOLBAR_ICAL'); ?>]
						</a>
						<?php if (!$this->user->guest && $token = (new Registry($this->user->params))->get('token')) { ?>
							<a href="<?php echo $this->router->getCalendarIcalRoute($calendar->getId(), $token); ?>" class="dp-link dp-link-ical">
								[<?php echo $this->translate('COM_DPCALENDAR_VIEW_CALENDAR_TOOLBAR_PRIVATE_ICAL'); ?>]
							</a>
						<?php } ?>
						<?php if (!$calendar instanceof ExternalCalendarInterface && !DPCalendarHelper::isFree() && !$this->user->guest) { ?>
							<?php $url = '/components/com_dpcalendar/caldav.php/calendars/' . $this->user->username . '/dp-' . $calendar->getId(); ?>
							<a href="<?php echo trim(Uri::base(), '/') . $url; ?>" class="dp-link dp-link-caldav">
								[<?php echo $this->translate('COM_DPCALENDAR_VIEW_PROFILE_TABLE_CALDAV_URL_LABEL'); ?>]
							</a>
						<?php } ?>
					<?php } ?>
				</div>
				<div class="dp-calendar__description">
					<div class="dp-calendar__event-text"><?php echo $calendar->event->beforeDisplayContent; ?></div>
					<div class="dp-calendar__description-text"><?php echo $calendar->getDescription(); ?></div>
					<div class="dp-calendar__event-text"><?php echo $calendar->event->afterDisplayContent; ?></div>
				</div>
			</div>
		<?php } ?>
	</div>
</div>
