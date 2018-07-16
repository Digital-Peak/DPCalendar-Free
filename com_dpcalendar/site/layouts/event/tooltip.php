<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2018 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
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
				'dateFormat' => $params->get('event_date_format', 'm.d.Y'),
				'timeFormat' => $params->get('event_time_format', 'g:i a')
			]
		); ?>
	</div>
	<a href="<?php echo $displayData['router']->getEventRoute($event->id, $event->catid); ?>" class="dp-event-tooltip__link dp-link">
		<?php echo $event->title; ?>
	</a>
	<?php if ($params->get('tooltip_show_description', 1)) { ?>
		<div class="dp-event-tooltip__description">
			<?php echo JHtml::_('string.truncate', $event->description, 100); ?>
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
			<a href="<?php echo $displayData['router']->getEventFormRoute($event->id, $return); ?>" class="dp-event-tooltip__action dp-link">
				<?php echo $displayData['layoutHelper']->renderLayout(
					'block.icon',
					['icon' => \DPCalendar\HTML\Block\Icon::EDIT, 'title' => $displayData['translator']->translate('JACTION_EDIT')]
				); ?>
			</a>
		<?php } ?>
		<?php if ($calendar->canDelete || ($calendar->canEditOwn && $event->created_by == $user->id)) { ?>
			<a href="<?php echo $displayData['router']->getEventDeleteRoute($event->id, $return); ?>" class="dp-event-tooltip__action dp-link">
				<?php echo $displayData['layoutHelper']->renderLayout(
					'block.icon',
					['icon' => \DPCalendar\HTML\Block\Icon::DELETE, 'title' => $displayData['translator']->translate('JACTION_DELETE')]
				); ?>
			</a>
		<?php } ?>
	</div>
</div>
