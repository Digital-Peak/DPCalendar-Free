<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2014 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Component\DPCalendar\Site\Model;

\defined('_JEXEC') or die();

use DigitalPeak\Component\DPCalendar\Administrator\Helper\DPCalendarHelper;
use DigitalPeak\Component\DPCalendar\Administrator\Model\EventModel;
use Joomla\CMS\Access\Access;
use Joomla\CMS\Application\CMSWebApplicationInterface;
use Joomla\CMS\Application\SiteApplication;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Field\SubformField;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Mail\Mail;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\User\UserFactoryAwareInterface;
use Joomla\CMS\User\UserFactoryAwareTrait;

class FormModel extends EventModel implements UserFactoryAwareInterface
{
	use UserFactoryAwareTrait;

	public $typeAlias = 'com_dpcalendar.event';

	/**
	 * Invites the given users or groups to the event with the given id.
	 */
	public function invite(int $eventId, array $userIds, array $groups): bool
	{
		foreach ($groups as $groupId) {
			$userIds = array_merge($userIds, Access::getUsersByGroup($groupId));
		}
		$event = $this->bootComponent('dpcalendar')->getMVCFactory()->createModel('Event', 'Site')->getItem($eventId);

		if (!$event || !$event->params->get('access-invite')) {
			throw new \Exception('COM_DPCALENDAR_ALERT_NO_AUTH');
		}

		foreach (array_unique($userIds) as $uid) {
			$bookingModel = $this->bootComponent('dpcalendar')->getMVCFactory()->createModel('Booking', 'Administrator', ['ignore_request' => true]);
			$ticketsModel = $this->bootComponent('dpcalendar')->getMVCFactory()->createModel('Tickets', 'Administrator', ['ignore_request' => true]);

			$u = $this->getUserFactory()->loadUserById($uid);
			if ($u->guest !== 0) {
				continue;
			}

			// Don't send an invitation when the user already has a ticket
			$ticketsModel->setState('filter.ticket_holder', $u->id);
			if ($ticketsModel->getItems()) {
				continue;
			}

			$amount = [];
			if ($event->prices) {
				foreach ($event->prices as $key => $price) {
					$key               = preg_replace('/\D/', '', (string)$key);
					$amount[(int)$key] = 1;
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
	 */
	public function mailtickets(int $eventId, string $subject, string $body, array $ticketIds): bool
	{
		$event = $this->bootComponent('dpcalendar')->getMVCFactory()->createModel('Event', 'Site')->getItem($eventId);
		if (!$event || !$event->params->get('send-tickets-mail')) {
			throw new \Exception('COM_DPCALENDAR_ALERT_NO_AUTH');
		}

		$app = Factory::getApplication();

		// Flag if only the author should get a mail
		$onlyAuthor = $ticketIds === [-1];

		$subject = DPCalendarHelper::renderEvents([$event], $subject);
		$body    = DPCalendarHelper::renderEvents([$event], $body);

		if ($app instanceof CMSWebApplicationInterface) {
			$app->setUserState('com_dpcalendar.form.event.mailticketsdata.subject', $subject);
			$app->setUserState('com_dpcalendar.form.event.mailticketsdata.message', $body);
		}

		foreach ($event->tickets as $ticket) {
			if ($ticketIds && !\in_array($ticket->id, $ticketIds) && !$onlyAuthor) {
				continue;
			}

			$mailer = $this->getMailerFactory()->createMailer();
			$mailer->addReplyTo($this->getCurrentUser()->email);
			$mailer->setSubject($subject);
			$mailer->setBody($body);
			$mailer->addRecipient($onlyAuthor ? $this->getCurrentUser()->email : $ticket->email);
			if ($mailer instanceof Mail) {
				$mailer->IsHTML(true);
			}
			$mailer->Send();

			if ($onlyAuthor) {
				break;
			}
		}

		return true;
	}

	public function getReturnPage(): string
	{
		return base64_encode((string)$this->getState('return_page', ''));
	}

	protected function populateState()
	{
		$app = Factory::getApplication();

		// Load state from the request.
		$pk = $app->getInput()->getString('e_id', '');
		$this->setState('event.id', $pk);

		// Add compatibility variable for default naming conventions.
		$this->setState('form.id', $pk);

		$categoryId = $app->getInput()->getString('catid', '');
		$this->setState('event.catid', $categoryId);

		$return = $app->getInput()->get('return', null, 'base64');
		if (!$return || !Uri::isInternal(base64_decode((string)$return))) {
			$return = null;
		}

		$this->setState('return_page', $return ? base64_decode((string)$return) : null);

		$params = $app instanceof SiteApplication ? $app->getParams() : ComponentHelper::getParams('com_dpcalendar');
		if (!$params->get('event_form_fields_order_')) {
			$params->set(
				'event_form_fields_order_',
				ComponentHelper::getParams('com_dpcalendar')->get('event_form_fields_order_', new \stdClass())
			);
		}
		$this->setState('params', $params);

		$this->setState('layout', $app->getInput()->getCmd('layout'));
	}

	public function getForm($data = [], $loadData = true)
	{
		Form::addFormPath(JPATH_ADMINISTRATOR . '/components/com_dpcalendar/forms');

		return parent::getForm($data, $loadData);
	}

	protected function preprocessForm(Form $form, $data, $group = 'content')
	{
		$app = Factory::getApplication();

		parent::preprocessForm($form, $data, $group);
		$form->setFieldAttribute('user_id', 'type', 'hidden');

		$params = $this->getState('params');
		if (!$params && $app instanceof SiteApplication) {
			$params = $app->getParams();
		}

		$form->setFieldAttribute('start_date', 'format', $params->get('event_form_date_format', 'd.m.Y'));
		$form->setFieldAttribute('start_date', 'formatTime', $params->get('event_form_time_format', 'H:i'));
		$form->setFieldAttribute('end_date', 'format', $params->get('event_form_date_format', 'd.m.Y'));
		$form->setFieldAttribute('end_date', 'formatTime', $params->get('event_form_time_format', 'H:i'));
		$form->setFieldAttribute('scheduling_end_date', 'format', $params->get('event_form_date_format', 'd.m.Y'));
		$form->setFieldAttribute('xreference', 'readonly', true);

		// Set the date format on existing subforms
		$exdates = $form->getField('exdates');
		if ($exdates instanceof SubformField) {
			$exdates->__get('input');
			$exdates->loadSubForm()->setFieldAttribute('date', 'format', DPCalendarHelper::getComponentParameter('event_form_date_format', 'd.m.Y'));
			foreach (array_keys(array_filter((array)$exdates->__get('value'))) as $key) {
				// @phpstan-ignore-next-line
				$form = Form::getInstance('subform.' . $key);
				$form->setFieldAttribute('date', 'format', DPCalendarHelper::getComponentParameter('event_form_date_format', 'd.m.Y'));
			}
		}

		$renderer = $this->bootComponent('dpcalendar')->getMVCFactory()->createModel('Layout', 'Administrator');
		$form->setFieldAttribute(
			'host_ids',
			'query',
			$renderer->renderLayout('form.hosts', ['field_name' => 'hosts', 'form' => $form])
		);

		// User field doesn't work on front
		if ($app instanceof SiteApplication) {
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
	}
}
