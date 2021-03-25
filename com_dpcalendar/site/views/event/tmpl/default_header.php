<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2015 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */
defined('_JEXEC') or die();

$event      = $this->event;
$eventRoute = $this->router->getEventRoute($event->id, $event->catid, false, true);

$mailtoUrl = '';
if (file_exists(JPATH_SITE . '/components/com_mailto/helpers/mailto.php')) {
	require_once JPATH_SITE . '/components/com_mailto/helpers/mailto.php';
	$uri       = JUri::getInstance()->toString(['scheme', 'host', 'port']);
	$mailtoUrl = 'index.php?option=com_mailto&tmpl=component&link=' . MailToHelper::addLink($uri . $eventRoute);
}

// Compile the Google url
$startDate  = $this->dateHelper->getDate($event->start_date, $event->all_day);
$endDate    = $this->dateHelper->getDate($event->end_date, $event->all_day);
$copyFormat = $event->all_day ? 'Ymd' : 'Ymd\THis';
if ($event->all_day) {
	$endDate->modify('+1 day');
}
$googleUrl = 'http://www.google.com/calendar/render?action=TEMPLATE&text=' . urlencode($event->title);
$googleUrl .= '&dates=' . $startDate->format($copyFormat, true) . '%2F' . $endDate->format($copyFormat, true);
$googleUrl .= '&location=' . urlencode(\DPCalendar\Helper\Location::format($event->locations));
$googleUrl .= '&details=' . urlencode(JHtml::_('string.truncate', $event->description, 200));
$googleUrl .= '&hl=' . DPCalendarHelper::getFrLanguage() . '&ctz=' . $startDate->getTimezone()->getName();
$googleUrl .= '&sf=true&output=xml';

$icalUrl = $this->router->route('index.php?option=com_dpcalendar&view=event&format=raw&id=' . $event->id . '&calid=' . $event->catid);

$checkinUrl = 'index.php?option=com_dpcalendar&task=event.checkin';
$checkinUrl .= '&e_id=' . $event->id;
$checkinUrl .= '&' . JSession::getFormToken() . '=1';

$return = JUri::getInstance(!empty($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], 'layout=edit') === false ?
	$_SERVER['HTTP_REFERER'] : 'index.php?ItemId=' . $this->input->getInt('Itemid', 0));

$deleteUrl = 'index.php?option=com_dpcalendar&task=event.delete&tmpl=';
$deleteUrl .= $this->input->getWord('tmpl') . '&return=' . base64_encode($return) . '&e_id=';
$this->translator->translateJS('COM_DPCALENDAR_CONFIRM_DELETE');
?>
<div class="com-dpcalendar-event__actions dp-button-bar dp-print-hide">
	<?php if ($this->params->get('event_show_print', 1)) { ?>
		<button type="button" class="dp-button dp-button-print" data-selector=".com-dpcalendar-event">
			<?php echo $this->layoutHelper->renderLayout('block.icon', ['icon' => \DPCalendar\HTML\Block\Icon::PRINTING]); ?>
			<?php echo $this->translate('COM_DPCALENDAR_VIEW_CALENDAR_TOOLBAR_PRINT'); ?>
		</button>
	<?php } ?>
	<?php if ($mailtoUrl && $this->params->get('event_show_mail', 1)) { ?>
		<button type="button" class="dp-button dp-button-mail" data-mailtohref="<?php echo $mailtoUrl; ?>">
			<?php echo $this->layoutHelper->renderLayout('block.icon', ['icon' => \DPCalendar\HTML\Block\Icon::MAIL]); ?>
			<?php echo $this->translate('JGLOBAL_EMAIL'); ?>
		</button>
	<?php } ?>
	<?php if ($this->params->get('event_show_copy', '1')) { ?>
		<button type="button" class="dp-button dp-button-action dp-button-copy-google" data-href="<?php echo $googleUrl; ?>" data-target="new">
			<?php echo $this->layoutHelper->renderLayout('block.icon', ['icon' => \DPCalendar\HTML\Block\Icon::DOWNLOAD]); ?>
			<?php echo $this->translate('COM_DPCALENDAR_FIELD_CONFIG_EVENT_LABEL_COPY_GOOGLE'); ?>
		</button>
		<button type="button" class="dp-button dp-button-action dp-button-copy-ical" data-href="<?php echo $icalUrl; ?>" data-target="new">
			<?php echo $this->layoutHelper->renderLayout('block.icon', ['icon' => \DPCalendar\HTML\Block\Icon::DOWNLOAD]); ?>
			<?php echo $this->translate('COM_DPCALENDAR_FIELD_CONFIG_EVENT_LABEL_COPY_OUTLOOK'); ?>
		</button>
	<?php } ?>
	<?php if (\DPCalendar\Helper\Booking::openForBooking($event) && $event->params->get('access-invite') && !DPCalendarHelper::isFree()) { ?>
		<button type="button" class="dp-button dp-button-action dp-button-invite"
				data-href="<?php echo DPCalendarHelperRoute::getInviteRoute($event); ?>">
			<?php echo $this->layoutHelper->renderLayout('block.icon', ['icon' => \DPCalendar\HTML\Block\Icon::SIGNUP]); ?>
			<?php echo $this->translate('COM_DPCALENDAR_INVITE'); ?>
		</button>
	<?php } ?>
	<?php if ($event->capacity != '0' && $event->params->get('access-tickets') && !DPCalendarHelper::isFree()) { ?>
		<button type="button" class="dp-button dp-button-action dp-button-tickets"
				data-href="<?php echo DPCalendarHelperRoute::getTicketsRoute(null, $event->id); ?>">
			<?php echo $this->layoutHelper->renderLayout('block.icon', ['icon' => \DPCalendar\HTML\Block\Icon::SIGNUP]); ?>
			<?php echo $this->translate('COM_DPCALENDAR_BOOKING_FIELD_TICKETS_LABEL'); ?>
		</button>
	<?php } ?>
	<?php if ($event->capacity != '0' && $event->params->get('access-bookings') && !DPCalendarHelper::isFree()) { ?>
		<button type="button" class="dp-button dp-button-action dp-button-bookings"
				data-href="<?php echo DPCalendarHelperRoute::getBookingsRoute($event->id); ?>">
			<?php echo $this->layoutHelper->renderLayout('block.icon', ['icon' => \DPCalendar\HTML\Block\Icon::SIGNUP]); ?>
			<?php echo $this->translate('COM_DPCALENDAR_BOOKINGS'); ?>
		</button>
	<?php } ?>
	<?php if ($event->params->get('access-edit')) { ?>
		<?php if ($event->checked_out && $this->user->id != $event->checked_out) { ?>
			<?php $this->app->enqueueMessage(
				JText::sprintf('COM_DPCALENDAR_VIEW_EVENT_CHECKED_OUT_BY', JFactory::getUser($event->checked_out)->name),
				'warning'
			); ?>
		<?php } ?>
		<?php if ($event->checked_out && $this->user->id != $event->checked_out && $this->user->authorise('core.manage', 'com_checkin')) { ?>
			<button type="button" class="dp-button dp-button-action dp-button-checkin" data-href="<?php echo $checkinUrl; ?>">
				<?php echo $this->layoutHelper->renderLayout('block.icon', ['icon' => \DPCalendar\HTML\Block\Icon::LOCK]); ?>
				<?php echo $this->translate('JLIB_HTML_CHECKIN'); ?>
			</button>
		<?php } ?>
		<?php if (!$event->checked_out || $this->user->id == $event->checked_out) { ?>
			<button type="button" class="dp-button dp-button-action dp-button-edit"
					data-href="<?php echo $this->router->getEventFormRoute($event->id, JUri::getInstance()); ?>">
				<?php echo $this->layoutHelper->renderLayout('block.icon', ['icon' => \DPCalendar\HTML\Block\Icon::EDIT]); ?>
				<?php echo $this->translate('COM_DPCALENDAR_VIEW_FORM_BUTTON_EDIT_EVENT'); ?>
			</button>
		<?php } ?>
	<?php } ?>
	<?php if ($event->params->get('access-delete')) { ?>
		<button type="button" class="dp-button dp-button-action dp-button-delete dp-action-delete"
				data-href="<?php echo $this->router->route($deleteUrl . $event->id); ?>">
			<?php echo $this->layoutHelper->renderLayout('block.icon', ['icon' => \DPCalendar\HTML\Block\Icon::DELETE]); ?>
			<?php echo $this->translate('COM_DPCALENDAR_DELETE'); ?>
		</button>
	<?php } ?>
	<?php if ($event->original_id > 0 && $event->params->get('access-delete')) { ?>
		<button type="button" class="dp-button dp-button-action dp-button-delete-series dp-action-delete"
				data-href="<?php echo $this->router->route($deleteUrl . $event->original_id); ?>">
			<?php echo $this->layoutHelper->renderLayout('block.icon', ['icon' => \DPCalendar\HTML\Block\Icon::DELETE]); ?>
			<?php echo $this->translate('COM_DPCALENDAR_DELETE_SERIES'); ?>
		</button>
	<?php } ?>
</div>
