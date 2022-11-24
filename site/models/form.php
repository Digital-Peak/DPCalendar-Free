<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2014 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

defined('_JEXEC') or die();

use DPCalendar\Helper\DPCalendarHelper;
use DPCalendar\Helper\LayoutHelper;
use Joomla\CMS\Access\Access;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\User\User;

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
			$userIds = array_merge($userIds, Access::getUsersByGroup($groupId));
		}
		$event = BaseDatabaseModel::getInstance('Event', 'DPCalendarModel')->getItem($eventId);

		if (!$event->params->get('access-invite')) {
			throw new Exception('COM_DPCALENDAR_ALERT_NO_AUTH');
		}

		BaseDatabaseModel::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_dpcalendar/models');

		foreach (array_unique($userIds) as $uid) {
			$bookingModel = BaseDatabaseModel::getInstance('Booking', 'DPCalendarModel', ['ignore_request' => true]);
			$ticketsModel = BaseDatabaseModel::getInstance('Tickets', 'DPCalendarModel', ['ignore_request' => true]);

			$u = User::getInstance($uid);
			if ($u->guest) {
				continue;
			}

			// Don't send an invitation when the user already has a ticket
			$ticketsModel->setState('filter.ticket_holder', $u->id);
			if ($ticketsModel->getItems()) {
				continue;
			}

			$amount = [];
			if ($event->price) {
				foreach ($event->price->value as $index => $value) {
					$amount[$index] = 1;
				}
			} else {
				$amount[0] = 1;
			}

			$bookingModel->save(
				[
					'event_id' => [$event->id => ['tickets' => $amount]],
					'name'     => $u->name,
					'email'    => $u->email,
					'user_id'  => $u->id,
					'country'  => 0,
					'state'    => 5
				],
				true
			);
		}

		return true;
	}

	/**
	 * Sends a mail to the ticket holders. If the ticket holders are an array with one element
	 * and the value -1, then the mail is sent to the currently logged in user.
	 *
	 * @param integer $eventId
	 * @param string  $subject
	 * @param string  $body
	 * @param array   $ticketIds
	 */
	public function mailtickets(int $eventId, string $subject, string $body, array $ticketIds)
	{
		$event = BaseDatabaseModel::getInstance('Event', 'DPCalendarModel')->getItem($eventId);
		if (!$event->params->get('send-tickets-mail')) {
			throw new Exception('COM_DPCALENDAR_ALERT_NO_AUTH');
		}

		BaseDatabaseModel::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_dpcalendar/models');

		// Flag if only the author should get a mail
		$onlyAuthor = $ticketIds === [-1];

		foreach ($event->tickets as $ticket) {
			if ($ticketIds && !in_array($ticket->id, $ticketIds) && !$onlyAuthor) {
				continue;
			}

			$mailer = Factory::getMailer();
			$mailer->addReplyTo(Factory::getUser()->email);
			$mailer->setSubject(DPCalendarHelper::renderEvents([$event], $subject));
			$mailer->setBody(DPCalendarHelper::renderEvents([$event], $body));
			$mailer->IsHTML(true);
			$mailer->AddAddress($onlyAuthor ? Factory::getUser()->email : $ticket->email);
			$mailer->Send();

			if ($onlyAuthor) {
				break;
			}
		}

		return true;
	}

	public function getReturnPage()
	{
		return base64_encode($this->getState('return_page', ''));
	}

	protected function populateState()
	{
		$app = Factory::getApplication();

		// Load state from the request.
		$pk = Factory::getApplication()->input->getString('e_id');
		$this->setState('event.id', $pk);

		// Add compatibility variable for default naming conventions.
		$this->setState('form.id', $pk);

		$categoryId = Factory::getApplication()->input->getString('catid');
		$this->setState('event.catid', $categoryId);

		$return = Factory::getApplication()->input->get('return', null, 'default', 'base64');
		if (!$return || !Uri::isInternal(base64_decode($return))) {
			$return = null;
		}

		$this->setState('return_page', $return ? base64_decode($return) : null);

		$params = method_exists($app, 'getParams') ? $app->getParams() : ComponentHelper::getParams('com_dpcalendar');
		if (!$params->get('event_form_fields_order_')) {
			$params->set(
				'event_form_fields_order_',
				ComponentHelper::getParams('com_dpcalendar')->get('event_form_fields_order_', new stdClass())
			);
		}
		$this->setState('params', $params);

		$this->setState('layout', Factory::getApplication()->input->getCmd('layout'));
	}

	public function getForm($data = [], $loadData = true)
	{
		Form::addFormPath(JPATH_ADMINISTRATOR . '/components/com_dpcalendar/models/forms');

		return parent::getForm($data, $loadData);
	}

	protected function preprocessForm(Form $form, $data, $group = 'content')
	{
		$return = parent::preprocessForm($form, $data, $group);
		$form->setFieldAttribute('user_id', 'type', 'hidden');

		$params = $this->getState('params');
		if (!$params) {
			$params = Factory::getApplication()->getParams();
		}

		$form->setFieldAttribute('start_date', 'format', $params->get('event_form_date_format', 'd.m.Y'));
		$form->setFieldAttribute('start_date', 'formatTime', $params->get('event_form_time_format', 'H:i'));
		$form->setFieldAttribute('end_date', 'format', $params->get('event_form_date_format', 'd.m.Y'));
		$form->setFieldAttribute('end_date', 'formatTime', $params->get('event_form_time_format', 'H:i'));
		$form->setFieldAttribute('scheduling_end_date', 'format', $params->get('event_form_date_format', 'd.m.Y'));
		$form->setFieldAttribute('xreference', 'readonly', true);

		$renderer = new LayoutHelper();
		$form->setFieldAttribute(
			'host_ids',
			'query',
			$renderer->renderLayout('form.hosts', ['field_name' => 'hosts', 'form' => $form])
		);

		// User field doesn't work on front
		if (Factory::getApplication()->isClient('site')) {
			$form->setFieldAttribute('created_by', 'type', 'sql');
			$form->setFieldAttribute('created_by', 'class', 'dp-select');
			$form->setFieldAttribute('created_by', 'key_field', 'value');
			$form->setFieldAttribute('created_by', 'value_field', 'text');
			$form->setFieldAttribute(
				'created_by',
				'query',
				$renderer->renderLayout('form.author', ['field_name' => 'created_by', 'form' => $form])
			);
			$form->setFieldAttribute('modified_by', 'type', 'sql');
			$form->setFieldAttribute('modified_by', 'class', 'dp-select');
			$form->setFieldAttribute('modified_by', 'key_field', 'value');
			$form->setFieldAttribute('modified_by', 'value_field', 'text');
			$form->setFieldAttribute(
				'modified_by',
				'query',
				$renderer->renderLayout('form.author', ['field_name' => 'modified_by', 'form' => $form])
			);
		}

		return $return;
	}
}
