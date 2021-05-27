<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2015 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

defined('_JEXEC') or die();

$event = $displayData['event'];
if (!$event) {
	return;
}
$params = $displayData['params'];
if (!$params) {
	$params = new \Joomla\Registry\Registry();
}

// Compile the return url
$return = $displayData['input']->getInt('Itemid', null);
if (!empty($return)) {
	$uri    = clone JUri::getInstance();
	$uri    = $uri->toString(['scheme', 'host', 'port']);
	$return = $uri . $displayData['router']->route('index.php?Itemid=' . $return, false);
}

$calendar = DPCalendarHelper::getCalendar($event->catid);
$user     = !empty($displayData['user']) ? $displayData['user'] : JFactory::getUser();
?>
<div class="dp-event-tooltip">
	<div class="dp-event-tooltip__date">
		<?php echo $displayData['layoutHelper']->renderLayout(
			'event.datestring',
			[
				'event'      => $event,
				'dateFormat' => $params->get('event_date_format', 'd.m.Y'),
				'timeFormat' => $params->get('event_time_format', 'H:i')
			]
		); ?>
	</div>
	<a href="<?php echo $displayData['router']->getEventRoute($event->id, $event->catid); ?>" class="dp-event-tooltip__link dp-link">
		<?php echo $event->title; ?>
	</a>
	<?php if ($event->state == 3) { ?>
		<span class="dp-event-tooltip_canceled"><?php echo $displayData['translator']->translate('COM_DPCALENDAR_FIELD_VALUE_CANCELED'); ?></span>
	<?php } ?>
	<?php if ($event->state == 0) { ?>
		<span class="dp-event-tooltip_unpublished"><?php echo $displayData['translator']->translate('JUNPUBLISHED'); ?></span>
	<?php } ?>
	<?php if ($params->get('tooltip_show_description', 1) && $params->get('description_length', 100)) { ?>
		<div class="dp-event-tooltip__description">
			<?php echo JHtml::_('string.truncateComplex', $event->description, $params->get('description_length', 100)); ?>
		</div>
	<?php } ?>
	<div class="dp-event-tooltip__actions dp-button-bar">
		<?php if (\DPCalendar\Helper\Booking::openForBooking($event)) { ?>
			<a href="<?php echo $displayData['router']->getBookingFormRouteFromEvent($event, $return); ?>" class="dp-event-tooltip__action dp-link">
				<?php echo $displayData['layoutHelper']->renderLayout(
					'block.icon',
					['icon' => \DPCalendar\HTML\Block\Icon::PLUS, 'title' => $displayData['translator']->translate('COM_DPCALENDAR_BOOK')]
				); ?>
			</a>
		<?php } ?>
		<?php if ($calendar->canEdit || ($calendar->canEditOwn && $event->created_by == $user->id)) { ?>
			<?php if ($event->checked_out && $user->id != $event->checked_out) { ?>
				<?php echo $displayData['layoutHelper']->renderLayout(
					'block.icon',
					[
						'icon'  => \DPCalendar\HTML\Block\Icon::LOCK,
						'title' => JText::sprintf('COM_DPCALENDAR_VIEW_EVENT_CHECKED_OUT_BY', JFactory::getUser($event->checked_out)->name)
					]
				); ?>
			<?php } else { ?>
				<a href="<?php echo $displayData['router']->getEventFormRoute($event->id, $return); ?>" class="dp-event-tooltip__action dp-link">
					<?php echo $displayData['layoutHelper']->renderLayout(
						'block.icon',
						['icon' => \DPCalendar\HTML\Block\Icon::EDIT, 'title' => $displayData['translator']->translate('JACTION_EDIT')]
					); ?>
				</a>
			<?php } ?>
		<?php } ?>
		<?php if ($calendar->canDelete || ($calendar->canEditOwn && $event->created_by == $user->id)) { ?>
			<a href="<?php echo $displayData['router']->getEventDeleteRoute($event->id, $return); ?>"
			   class="dp-event-tooltip__action dp-event-tooltip__action-delete dp-link">
				<?php echo $displayData['layoutHelper']->renderLayout(
					'block.icon',
					['icon' => \DPCalendar\HTML\Block\Icon::DELETE, 'title' => $displayData['translator']->translate('JACTION_DELETE')]
				); ?>
			</a>
		<?php } ?>
		<?php if ($event->capacity === null || $event->capacity > 0) { ?>
			<span class="dp-event-tooltip__capacity">
				<?php echo $displayData['layoutHelper']->renderLayout(
					'block.icon',
					[
						'icon'  => \DPCalendar\HTML\Block\Icon::USERS,
						'title' => $displayData['translator']->translate('COM_DPCALENDAR_FIELD_CAPACITY_LABEL')
					]
				); ?>
				<?php if ($event->capacity === null) { ?>
					<?php echo $displayData['translator']->translate('COM_DPCALENDAR_FIELD_CAPACITY_UNLIMITED'); ?>
				<?php } else { ?>
					<?php echo $event->capacity_used . '/' . (int)$event->capacity; ?>
				<?php } ?>
			</span>
			<span class="dp-event-tooltip__price">
				<?php echo $displayData['layoutHelper']->renderLayout(
					'block.icon',
					[
						'icon'  => \DPCalendar\HTML\Block\Icon::MONEY,
						'title' => $displayData['translator']->translate('COM_DPCALENDAR_FIELD_PRICE_LABEL')
					]
				); ?>
				<?php echo $displayData['translator']->translate($event->price ? 'COM_DPCALENDAR_VIEW_CALENDAR_PAID_EVENT' : 'COM_DPCALENDAR_VIEW_CALENDAR_FREE_EVENT'); ?>
			</span>
		<?php } ?>
	</div>
</div>
