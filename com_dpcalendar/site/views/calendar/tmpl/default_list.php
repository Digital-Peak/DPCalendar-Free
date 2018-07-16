<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2018 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */
defined('_JEXEC') or die();

$params = $this->params;
if ($params->get('show_selection', 1) == 2) {
	return;
}
?>
<div class="com-dpcalendar-calendar__list com-dpcalendar-calendar__list_<?php echo $params->get('show_selection', 1) == 3 ? '' : 'hidden'; ?>">
	<?php foreach ($this->doNotListCalendars as $calendar) { ?>
		<?php $value = html_entity_decode(
			JRoute::_(
				'index.php?option=com_dpcalendar&view=events&format=raw&limit=0' .
				'&ids=' . $calendar->id .
				'&my=' . $params->get('show_my_only_calendar', '0') .
				'&Itemid=' . $this->input->getInt('Itemid', 0)
			)
		); ?>
		<dl class="com-dpcalendar-calendar__calendar-description dp-description">
			<dt class="dp-description__label">
				<label for="cal-<?php echo $calendar->id; ?>">
					<input type="checkbox" name="<?php echo $calendar->id; ?>" value="<?php echo $value; ?>"  id="cal-<?php echo $calendar->id; ?>"class="dp-input dp-input-checkbox">
					<span style="color: #<?php echo $calendar->color; ?>">
						<?php echo str_pad(' ' . $calendar->title, strlen(' ' . $calendar->title) + $calendar->level - 1, '-', STR_PAD_LEFT); ?>
						<?php if ((!empty($calendar->icalurl) || !$calendar->external) && $params->get('show_export_links', 1)) { ?>
							<a href="<?php echo $this->router->getCalendarIcalRoute($calendar->id); ?>" class="dp-link">
								[<?php echo $this->translate('COM_DPCALENDAR_VIEW_CALENDAR_TOOLBAR_ICAL'); ?>]
							</a>
							<?php if (!$this->user->guest && $token = (new \Joomla\Registry\Registry($this->user->params))->get('token')) { ?>
								<a href="<?php echo $this->router->getCalendarIcalRoute($calendar->id, $token); ?>" class="dp-link">
									[<?php echo $this->translate('COM_DPCALENDAR_VIEW_CALENDAR_TOOLBAR_PRIVATE_ICAL'); ?>]
								</a>
							<?php } ?>
							<?php if (!$calendar->external && !DPCalendarHelper::isFree() && !$this->user->guest) { ?>
								<?php $url = '/components/com_dpcalendar/caldav.php/calendars/' . $this->user->username . '/dp-' . $calendar->id; ?>
								<a href="<?php echo trim(JUri::base(), '/') . $url; ?>" class="dp-link">
									[<?php echo $this->translate('COM_DPCALENDAR_VIEW_PROFILE_TABLE_CALDAV_URL_LABEL'); ?>]
								</a>
							<?php } ?>
						<?php } ?>
					</span>
				</label>
			</dt>
			<dl class="dp-description__description"><?php echo $calendar->description; ?></dl>
		</dl>
	<?php } ?>
</div>
<div class="com-dpcalendar-calendar__toggle dp-toggle">
	<div class="dp-toggle__up dp-toggle_<?php echo $params->get('show_selection', 1) == 3 ? '' : 'hidden'; ?>"
	     data-direction="up">
		<?php echo $this->layoutHelper->renderLayout('block.icon', ['icon' => \DPCalendar\HTML\Block\Icon::UP]); ?>
	</div>
	<div class="dp-toggle__down dp-toggle_<?php echo $params->get('show_selection', 1) == 3 ? 'hidden' : ''; ?>"
	     data-direction="down">
		<?php echo $this->layoutHelper->renderLayout('block.icon', ['icon' => \DPCalendar\HTML\Block\Icon::DOWN]); ?>
	</div>
</div>
