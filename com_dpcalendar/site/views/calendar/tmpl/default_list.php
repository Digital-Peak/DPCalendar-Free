<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2018 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */
defined('_JEXEC') or die();

if ($this->params->get('show_selection', 1) == 2) {
	return;
}
?>
<div class="com-dpcalendar-calendar__list com-dpcalendar-calendar__list_<?php echo $this->params->get('show_selection', 1) == 3 ? '' : 'hidden'; ?>">
	<?php if (count($this->doNotListCalendars) >= 10) { ?>
		<div class="com-dpcalendar-calendar__list-toggle">
			<input type="checkbox" id="calendars-toggle" class="dp-input dp-input-checkbox" checked>
			<label for="calendars-toggle" class="dp-input-label"><?php echo $this->translate('COM_DPCALENDAR_VIEW_CALENDAR_TOGGLE'); ?></label>
		</div>
	<?php } ?>
	<div class="com-dpcalendar-calendar__calendars">
		<?php foreach ($this->doNotListCalendars as $calendar) { ?>
			<?php $style = 'background-color: #' . $calendar->color . ';'; ?>
			<?php $style .= 'border-color: #' . $calendar->color . ';'; ?>
			<?php $style .= 'color: #' . \DPCalendar\Helper\DPCalendarHelper::getOppositeBWColor($calendar->color); ?>
			<?php $icalRoute = $this->router->getCalendarIcalRoute($calendar->id); ?>
			<div class="dp-calendar">
				<label for="cal-<?php echo $calendar->id; ?>" class="dp-input-label dp-calendar__label">
					<input type="checkbox" name="cal-<?php echo $calendar->id; ?>" value="<?php echo $calendar->id; ?>"
						   id="cal-<?php echo $calendar->id; ?>" class="dp-input dp-input-checkbox dp-calendar__input" style="<?php echo $style; ?>">
					<div class="dp-calendar__title">
						<?php echo str_pad(' ' . $calendar->title, strlen(' ' . $calendar->title) + $calendar->level - 1, '-', STR_PAD_LEFT); ?>
					</div>
					<div class="dp-calendar__event-text"><?php echo $calendar->event->afterDisplayTitle; ?></div>
				</label>
				<div class="dp-calendar__links">
					<?php if ((!empty($calendar->icalurl) || !$calendar->external) && $this->params->get('show_export_links', 1)) { ?>
						<a href="<?php echo str_replace(['http://', 'https://'], 'webcal://', $icalRoute); ?>"
						   class="dp-link dp-link-subscribe">
							[<?php echo $this->translate('COM_DPCALENDAR_VIEW_CALENDAR_TOOLBAR_SUBSCRIBE'); ?>]
						</a>
						<a href="<?php echo $icalRoute; ?>" class="dp-link">
							[<?php echo $this->translate('COM_DPCALENDAR_VIEW_CALENDAR_TOOLBAR_ICAL'); ?>]
						</a>
						<?php if (!$this->user->guest && $token = (new \Joomla\Registry\Registry($this->user->params))->get('token')) { ?>
							<a href="<?php echo $this->router->getCalendarIcalRoute($calendar->id, $token); ?>" class="dp-link dp-link-ical">
								[<?php echo $this->translate('COM_DPCALENDAR_VIEW_CALENDAR_TOOLBAR_PRIVATE_ICAL'); ?>]
							</a>
						<?php } ?>
						<?php if (!$calendar->external && !DPCalendarHelper::isFree() && !$this->user->guest) { ?>
							<?php $url = '/components/com_dpcalendar/caldav.php/calendars/' . $this->user->username . '/dp-' . $calendar->id; ?>
							<a href="<?php echo trim(JUri::base(), '/') . $url; ?>" class="dp-link dp-link-caldav">
								[<?php echo $this->translate('COM_DPCALENDAR_VIEW_PROFILE_TABLE_CALDAV_URL_LABEL'); ?>]
							</a>
						<?php } ?>
					<?php } ?>
				</div>
				<div class="dp-calendar__description">
					<div class="dp-calendar__event-text"><?php echo $calendar->event->beforeDisplayContent; ?></div>
					<div class="dp-calendar__description-text"><?php echo $calendar->description; ?></div>
					<div class="dp-calendar__event-text"><?php echo $calendar->event->afterDisplayContent; ?></div>
				</div>
			</div>
		<?php } ?>
	</div>
</div>
