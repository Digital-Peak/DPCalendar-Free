<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2015 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

\defined('_JEXEC') or die();

use DigitalPeak\Component\DPCalendar\Administrator\Calendar\CalendarInterface;
use DigitalPeak\Component\DPCalendar\Administrator\Helper\Booking;
use DigitalPeak\Component\DPCalendar\Administrator\HTML\Block\Icon;
use Joomla\CMS\Application\CMSApplicationInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Uri\Uri;
use Joomla\Registry\Registry;

$event = $displayData['event'];
if (!$event) {
	return;
}

$app = $displayData['app'];
if (!$app instanceof CMSApplicationInterface) {
	return;
}

$calendar = $app->bootComponent('dpcalendar')->getMVCFactory()->createModel('Calendar', 'Administrator')->getCalendar($event->catid);
if (!$calendar instanceof CalendarInterface) {
	return;
}

$params = $displayData['params'];
if (!$params) {
	$params = new Registry();
}

// Compile the return url
$return = $displayData['input']->getInt('Itemid', 0);
if (!empty($return)) {
	$uri    = clone Uri::getInstance();
	$uri    = $uri->toString(['scheme', 'host', 'port']);
	$return = $uri . $displayData['router']->route('index.php?Itemid=' . $return, false);
}

$user = empty($displayData['user']) ? $app->getIdentity() : $displayData['user'];

try {
	PluginHelper::importPlugin('content');
	$event->text = $event->description ?: '';
	$app->triggerEvent('onContentPrepare', ['com_dpcalendar.event', &$event, &$event->params, 0]);
	$event->description = $event->text;
} catch (\Throwable) {
}
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
	<div class="dp-event-tooltip__calendar">[<?php echo $calendar->getTitle(); ?>]</div>
	<?php if ($params->get('show_event_as_popup', '0') != '2') { ?>
		<a href="<?php echo $displayData['router']->getEventRoute($event->id, $event->catid); ?>" class="dp-event-tooltip__link dp-link">
			<?php echo $event->title; ?>
		</a>
	<?php } else { ?>
		<?php echo $event->title; ?>
	<?php } ?>
	<?php if ($event->state == 3) { ?>
		<span class="dp-event-tooltip_canceled"><?php echo $displayData['translator']->translate('COM_DPCALENDAR_FIELD_VALUE_CANCELED'); ?></span>
	<?php } ?>
	<?php if ($event->state == 0) { ?>
		<span class="dp-event-tooltip_unpublished"><?php echo $displayData['translator']->translate('JUNPUBLISHED'); ?></span>
	<?php } ?>
	<?php if ($params->get('tooltip_show_description', 1) && $params->get('description_length', 100)) { ?>
		<div class="dp-event-tooltip__description">
			<?php echo HTMLHelper::_('string.truncateComplex', $event->introText ?: ($event->description ?: ''), $params->get('description_length', 100)); ?>
		</div>
	<?php } ?>
	<div class="dp-event-tooltip__actions dp-button-bar">
		<?php if (Booking::openForBooking($event)) { ?>
			<a href="<?php echo $displayData['router']->getBookingFormRouteFromEvent($event, $return); ?>" class="dp-event-tooltip__action dp-link">
				<?php echo $displayData['layoutHelper']->renderLayout(
					'block.icon',
					['icon' => Icon::BOOK, 'title' => $displayData['translator']->translate('COM_DPCALENDAR_BOOK')]
				); ?>
			</a>
		<?php } ?>
		<?php if ($calendar->canEdit() || ($calendar->canEditOwn() && $event->created_by == $user->id)) { ?>
			<?php if ($event->checked_out && $user->id != $event->checked_out) { ?>
				<?php echo $displayData['layoutHelper']->renderLayout(
					'block.icon',
					[
						'icon'  => Icon::LOCK,
						// @phpstan-ignore-next-line
						'title' => Text::sprintf('COM_DPCALENDAR_VIEW_EVENT_CHECKED_OUT_BY', Factory::getUser($event->checked_out)->name)
					]
				); ?>
			<?php } else { ?>
				<a href="<?php echo $displayData['router']->getEventFormRoute($event->id, $return); ?>" class="dp-event-tooltip__action dp-link">
					<?php echo $displayData['layoutHelper']->renderLayout(
						'block.icon',
						['icon' => Icon::EDIT, 'title' => $displayData['translator']->translate('JACTION_EDIT')]
					); ?>
				</a>
			<?php } ?>
		<?php } ?>
		<?php if (($calendar->canDelete() || ($calendar->canEditOwn() && $event->created_by == $user->id)) && (!$event->checked_out || $user->id == $event->checked_out)) { ?>
			<a href="<?php echo $displayData['router']->getEventDeleteRoute($event->id, $return); ?>"
				class="dp-event-tooltip__action dp-event-tooltip__action-delete dp-link">
				<?php echo $displayData['layoutHelper']->renderLayout(
					'block.icon',
					['icon' => Icon::DELETE, 'title' => $displayData['translator']->translate('JACTION_DELETE')]
				); ?>
			</a>
		<?php } ?>
		<?php if ($event->capacity === null || $event->capacity > 0) { ?>
			<span class="dp-event-tooltip__capacity">
				<?php echo $displayData['layoutHelper']->renderLayout(
					'block.icon',
					['icon' => Icon::USERS, 'title' => $displayData['translator']->translate('COM_DPCALENDAR_FIELD_CAPACITY_LABEL')]
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
					['icon' => Icon::MONEY, 'title' => $displayData['translator']->translate('COM_DPCALENDAR_FIELD_PRICES_LABEL')]
				); ?>
				<?php echo $displayData['translator']->translate('COM_DPCALENDAR_VIEW_CALENDAR_' . ($event->prices ? 'PAID' : 'FREE') . '_EVENT'); ?>
			</span>
		<?php } ?>
	</div>
</div>
