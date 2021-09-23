<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2014 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

defined('_JEXEC') or die();

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

	public function getReturnPage()
	{
		return base64_encode($this->getState('return_page'));
	}

	protected function populateState()
	{
		$app = Factory::getApplication();

		// Load state from the request.
		$pk = Factory::getApplication()->input->getVar('e_id');
		$this->setState('event.id', $pk);

		// Add compatibility variable for default naming conventions.
		$this->setState('form.id', $pk);

		$categoryId = Factory::getApplication()->input->getVar('catid');
		$this->setState('event.catid', $categoryId);

		$return = Factory::getApplication()->input->getVar('return', null, 'default', 'base64');

		if (!Uri::isInternal(base64_decode($return))) {
			$return = null;
		}

		$this->setState('return_page', base64_decode($return));

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

		// User field doesn't work on front
		if (Factory::getApplication()->isClient('site')) {
			$form->setFieldAttribute('created_by', 'type', 'sql');
			$form->setFieldAttribute('created_by', 'key_field', 'value');
			$form->setFieldAttribute('created_by', 'value_field', 'text');
			$form->setFieldAttribute(
				'created_by',
				'query',
				'select id as value, name as text from #__users union all select null, null order by text'
			);
			$form->setFieldAttribute('modified_by', 'type', 'sql');
			$form->setFieldAttribute('modified_by', 'key_field', 'value');
			$form->setFieldAttribute('modified_by', 'value_field', 'text');
			$form->setFieldAttribute(
				'modified_by',
				'query',
				'select id as value, name as text from #__users union all select null, null order by text'
			);
		}

		return $return;
	}
}
