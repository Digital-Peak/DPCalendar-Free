<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2018 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */

use CCL\Content\Element\Basic\Container;
use DPCalendar\CCL\Visitor\InlineStyleVisitor;

defined('_JEXEC') or die();

JLoader::import('components.com_dpcalendar.models.adminevent', JPATH_ADMINISTRATOR);
JLoader::import('components.com_dpcalendar.tables.event', JPATH_ADMINISTRATOR);

class DPCalendarModelForm extends DPCalendarModelAdminEvent
{

	public $typeAlias = 'com_dpcalendar.event';

	/**
	 * Invites the given users or groups to the event with the given id.
	 *
	 * @param integer $eventId
	 * @param array   $users
	 * @param array   $groups
	 */
	public function invite($eventId, $userIds, $groups)
	{
		foreach ($groups as $groupId) {
			$userIds = array_merge($userIds, JFactory::getACL()->getUsersByGroup($groupId));
		}
		$event = JModelLegacy::getInstance('Event', 'DPCalendarModel')->getItem($eventId);
		$lang  = JFactory::$language;

		JModelLegacy::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_dpcalendar/models');

		foreach (array_unique($userIds) as $uid) {
			$bookingModel = JModelLegacy::getInstance('Booking', 'DPCalendarModel', array('ignore_request' => true));
			$ticketsModel = JModelLegacy::getInstance('Tickets', 'DPCalendarModel', array('ignore_request' => true));

			$u = JUser::getInstance($uid);
			if ($u->guest) {
				continue;
			}

			// Don't send an invitation when the user already has a ticket
			$ticketsModel->setState('filter.ticket_holder', $u->id);
			if ($ticketsModel->getItems()) {
				continue;
			}

			if ($u->getParam('language') && JFactory::getLanguage()->getTag() != $u->getParam('language')) {
				JFactory::getConfig()->set('language', $u->getParam('language'));
				JFactory::$language = null;
				JFactory::getLanguage()->load('com_dpcalendar', JPATH_ADMINISTRATOR . '/components/com_dpcalendar');
			}

			$amount = array();
			if ($event->price) {
				foreach ($event->price->value as $index => $value) {
					$amount[$index] = 1;
				}
			} else {
				$amount[0] = 1;
			}

			$booking = $bookingModel->save(
				array(
					'event_id' => array($event->id => $amount),
					'name'     => $u->name,
					'email'    => $u->email,
					'user_id'  => $u->id
				),
				true
			);
			if (!$booking) {
				continue;
			}

			// Create the booking details for mail notification
			$params = clone JComponentHelper::getParams('com_dpcalendar');
			$params->set('show_header', false);

			$root = new Container('dp-booking');
			DPCalendarHelper::renderLayout(
				'booking.invoice',
				array(
					'booking' => $booking,
					'tickets' => $bookingModel->getTickets($booking->id),
					'params'  => $params,
					'root'    => $root
				)
			);
			$root->accept(new InlineStyleVisitor());

			$additionalVars = array(
				'acceptUrl'      => DPCalendarHelperRoute::getInviteChangeRoute($booking, true, true),
				'declineUrl'     => DPCalendarHelperRoute::getInviteChangeRoute($booking, false, true),
				'bookingDetails' => DPCalendarHelper::renderElement($root, $params),
				'bookingLink'    => DPCalendarHelperRoute::getBookingRoute($booking, true),
				'bookingUid'     => $booking->uid,
				'sitename'       => JFactory::getConfig()->get('sitename'),
				'user'           => $u->name
			);

			$subject = DPCalendarHelper::renderEvents(array($event), JText::_('COM_DPCALENDAR_INVITE_NOTIFICATION_EVENT_SUBJECT'));

			$body = DPCalendarHelper::renderEvents(array($event), JText::_('COM_DPCALENDAR_INVITE_NOTIFICATION_EVENT_BODY'), null, $additionalVars);

			$mailer = JFactory::getMailer();
			$mailer->setSubject($subject);
			$mailer->setBody($body);
			$mailer->IsHTML(true);
			$mailer->addRecipient($u->email);
			$mailer->Send();
		}

		// Resetting the language to it's old state
		JFactory::$language = $lang;

		return true;
	}

	public function getReturnPage()
	{
		return base64_encode($this->getState('return_page'));
	}

	protected function populateState()
	{
		$app = JFactory::getApplication();

		// Load state from the request.
		$pk = JFactory::getApplication()->input->getVar('e_id');
		$this->setState('event.id', $pk);

		// Add compatibility variable for default naming conventions.
		$this->setState('form.id', $pk);

		$categoryId = JFactory::getApplication()->input->getVar('catid');
		$this->setState('event.catid', $categoryId);

		$return = JFactory::getApplication()->input->getVar('return', null, 'default', 'base64');

		if (!JUri::isInternal(base64_decode($return))) {
			$return = null;
		}

		$this->setState('return_page', base64_decode($return));

		// Load the parameters.
		if ($app->isClient('site')) {
			$this->setState('params', $app->getParams());
		} else {
			$this->setState('params', JComponentHelper::getParams('com_dpcalendar'));
		}

		$this->setState('layout', JFactory::getApplication()->input->getCmd('layout'));
	}

	public function getForm($data = array(), $loadData = true)
	{
		JForm::addFormPath(JPATH_ADMINISTRATOR . '/components/com_dpcalendar/models/forms');

		return parent::getForm($data, $loadData);
	}

	protected function preprocessForm(JForm $form, $data, $group = 'content')
	{
		$return = parent::preprocessForm($form, $data, $group);
		$form->setFieldAttribute('user_id', 'type', 'hidden');

		$params = $this->getState('params');
		if (!$params) {
			$params = JFactory::getApplication()->getParams();
		}

		$form->setFieldAttribute('start_date', 'format', $params->get('event_form_date_format', 'm.d.Y'));
		$form->setFieldAttribute('start_date', 'formatTime', $params->get('event_form_time_format', 'g:i a'));
		$form->setFieldAttribute('end_date', 'format', $params->get('event_form_date_format', 'm.d.Y'));
		$form->setFieldAttribute('end_date', 'formatTime', $params->get('event_form_time_format', 'g:i a'));
		$form->setFieldAttribute('scheduling_end_date', 'format', $params->get('event_form_date_format', 'm.d.Y'));
		$form->setFieldAttribute('xreference', 'readonly', true);

		return $return;
	}
}
