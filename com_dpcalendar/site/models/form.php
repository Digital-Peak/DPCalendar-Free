<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2017 Digital Peak. All rights reserved.
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
		if ($app->isSite()) {
			$this->setState('params', $app->getParams());
		} else {
			$this->setState('params', JComponentHelper::getParams('com_dpcalendar'));
		}

		$this->setState('layout', JFactory::getApplication()->input->getCmd('layout'));
	}

	public function getForm($data = array(), $loadData = true)
	{
		JForm::addFormPath(JPATH_ADMINISTRATOR . '/components/com_dpcalendar/models/forms');

		/** @var JForm $form */
		$form = parent::getForm($data, $loadData);

		$params = $this->getState('params');
		if (!$params) {
			$params = JFactory::getApplication()->getParams();
		}

		// Set the default values from the params
		if (!$form->getValue('catid')) {
			$form->setValue('catid', null, $params->get('event_form_calid'));
		}
		if (!$form->getValue('show_end_time')) {
			$form->setValue('show_end_time', null, $params->get('event_form_show_end_time'));
		}
		if (!$form->getValue('all_day')) {
			$form->setValue('all_day', null, $params->get('event_form_all_day'));
		}
		if (!$form->getValue('color')) {
			$form->setValue('color', null, $params->get('event_form_color'));
		}
		if (!$form->getValue('url')) {
			$form->setValue('url', null, $params->get('event_form_url'));
		}
		if (!$form->getValue('description')) {
			$form->setValue('description', null, $params->get('event_form_description'));
		}
		if (!$form->getValue('capacity') && $params->get('event_form_capacity') > 0) {
			$form->setValue('capacity', null, $params->get('event_form_capacity'));
		}
		if (!$form->getValue('price')) {
			$form->setValue('price', null, $params->get('event_form_price'));
		}
		if (!$form->getValue('plugintype')) {
			$form->setValue('plugintype', null, $params->get('event_form_plugintype'));
		}
		if (!$form->getValue('ordertext')) {
			$form->setValue('ordertext', null, $params->get('event_form_ordertext'));
		}
		if (!$form->getValue('canceltext')) {
			$form->setValue('canceltext', null, $params->get('event_form_canceltext'));
		}
		if (!$form->getValue('payment_statement')) {
			$form->setValue('payment_statement', null, $params->get('event_form_payment_statement'));
		}
		if (!$form->getValue('access')) {
			$form->setValue('access', null, $params->get('event_form_access'));
		}
		if (!$form->getValue('access_content')) {
			$form->setValue('access_content', null, $params->get('event_form_access_content'));
		}
		if (!$form->getValue('featured')) {
			$form->setValue('featured', null, $params->get('event_form_featured'));
		}
		if (!$form->getValue('language')) {
			$form->setValue('language', null, $params->get('event_form_language'));
		}
		if (!$form->getValue('metakey')) {
			$form->setValue('metakey', null, $params->get('menu-meta_keywords'));
		}
		if (!$form->getValue('metadesc')) {
			$form->setValue('metadesc', null, $params->get('menu-meta_description'));
		}

		// Remove fields depending on the params
		if ($params->get('event_form_change_calid', '1') != '1') {
			$form->setFieldAttribute('catid', 'readonly', 'readonly');
		}
		if ($params->get('event_form_change_color', '1') != '1') {
			$form->removeField('color');
		}
		if ($params->get('event_form_change_url', '1') != '1') {
			$form->removeField('url');
		}
		if ($params->get('event_form_change_images', '1') != '1') {
			$form->removeGroup('images');
		}
		if ($params->get('event_form_change_description', '1') != '1') {
			$form->removeField('description');
		}
		if ($params->get('event_form_change_capacity', '1') != '1') {
			$form->removeField('capacity');
		}
		if ($params->get('event_form_change_capacity_used', '1') != '1') {
			$form->removeField('capacity_used');
		}
		if ($params->get('event_form_change_max_tickets', '1') != '1') {
			$form->removeField('max_tickets');
		}
		if ($params->get('event_form_change_price', '1') != '1') {
			$form->removeField('price');

			// We need to do it a second time because of the booking form
			$form->removeField('price');
		}
		if ($params->get('event_form_change_payment', '1') != '1') {
			$form->removeField('plugintype');
		}
		if ($params->get('event_form_change_order', '1') != '1') {
			$form->removeField('ordertext');
		}
		if ($params->get('event_form_change_cancellation', '1') != '1') {
			$form->removeField('canceltext');
		}
		if ($params->get('event_form_change_paystatement', '1') != '1') {
			$form->removeField('payment_statement');
		}
		if ($params->get('event_form_change_access', '1') != '1') {
			$form->removeField('access');
		}
		if ($params->get('event_form_change_access_content', '1') != '1') {
			$form->removeField('access_content');
		}
		if ($params->get('event_form_change_featured', '1') != '1') {
			$form->removeField('featured');
		}

		// Handle tabs
		if ($params->get('event_form_change_location', '1') != '1') {
			$form->removeField('location');
			$form->removeField('location_ids');
		}

		return $form;
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
		$form->setFieldAttribute('xreference', 'readonly', true);

		return $return;
	}
}
