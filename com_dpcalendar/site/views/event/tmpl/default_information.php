<?php

/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2018 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */
defined('_JEXEC') or die();

$params   = $this->params;
$calendar = DPCalendarHelper::getCalendar($this->event->catid);
$sd       = $this->dateHelper->getDate($this->event->start_date, $this->event->all_day);

$calendarLink = $this->router->getCalendarRoute($this->event->catid);
if ($calendarLink && $params->get('event_show_calendar', '1') == '2') {
	$calendarLink .= '#year=' . $sd->format('Y', true) . '&month=' . $sd->format('m', true) . '&day=' . $sd->format('d', true);
}
?>
<div class="com-dpcalendar-event__information">
	<?php if ($params->get('event_show_calendar', '1')) { ?>
		<dl class="dp-description">
			<dt class="dp-description__label"><?php echo $this->translate('COM_DPCALENDAR_CALENDAR'); ?></dt>
			<dd class="dp-description__description">
				<?php if ($calendarLink) { ?>
					<a href="<?php echo $calendarLink; ?>" class="dp-link"><?php echo $calendar->title; ?></a>
				<?php } else { ?>
					<?php echo $calendar != null ? $calendar->title : $this->event->catid; ?>
				<?php } ?>
			</dd>
		</dl>
	<?php } ?>
	<?php if ($params->get('event_show_date', '1')) { ?>
		<dl class="dp-description">
			<dt class="dp-description__label"><?php echo $this->translate('COM_DPCALENDAR_DATE'); ?></dt>
			<dd class="dp-description__description">
				<?php echo $this->dateHelper->getDateStringFromEvent(
					$this->event,
					$params->get('event_date_format', 'm.d.Y'),
					$params->get('event_time_format', 'g:i a')
				); ?>
			</dd>
		</dl>
	<?php } ?>
	<?php if ($this->event->locations && $params->get('event_show_location', '2')) { ?>
		<dl class="dp-description">
			<dt class="dp-description__label"><?php echo $this->translate('COM_DPCALENDAR_LOCATION'); ?></dt>
			<dd class="dp-description__description">
				<?php foreach ($this->event->locations as $location) { ?>
					<?php $url = $params->get('event_show_location', '2') == '1' ?
						$this->router->getLocationRoute($location) : '#dp-location-' . $location->id; ?>
					<a href="<?php echo $url; ?>" class="dp-link">
						<?php echo $location->title; ?>
						<?php if ($this->roomTitles) { ?>
							[<?php echo implode(', ', $this->roomTitles); ?>]
						<?php } ?>
					</a>
				<?php } ?>
			</dd>
		</dl>
	<?php } ?>
	<?php if ($this->authorName && $params->get('event_show_author', '1')) { ?>
		<dl class="dp-description">
			<dt class="dp-description__label"><?php echo $this->translate('COM_DPCALENDAR_FIELD_CONFIG_EVENT_LABEL_AUTHOR'); ?></dt>
			<dd class="dp-description__description">
				<?php if ($this->event->contact_link) { ?>
					<a href="<?php echo $this->event->contact_link; ?>" class="dp-link">
						<?php echo $this->authorName . $this->avatar; ?>
					</a>
				<?php } else { ?>
					<?php echo $this->authorName . $this->avatar; ?>
				<?php } ?>
			</dd>
		</dl>
	<?php } ?>
	<?php if ($this->event->url && $params->get('event_show_url', '1')) { ?>
		<dl class="dp-description">
			<dt class="dp-description__label"><?php echo $this->translate('COM_DPCALENDAR_FIELD_CONFIG_EVENT_LABEL_URL'); ?></dt>
			<dd class="dp-description__description">
				<?php $u = JUri::getInstance($this->event->url); ?>
				<a href="<?php echo $this->event->url; ?>"
				   class="dp-link"
				   target="<?php echo $u->getHost() && JUri::getInstance()->getHost() != $u->getHost() ? '_blank' : ''; ?>">
					<?php echo $this->event->url; ?>
				</a>
			</dd>
		</dl>
	<?php } ?>
	<div class="com-dpcalendar-event__event-text">
		<?php echo $this->event->displayEvent->beforeDisplayContent; ?>
	</div>
</div>
