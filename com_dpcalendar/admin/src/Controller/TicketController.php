<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2015 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Component\DPCalendar\Administrator\Controller;

defined('_JEXEC') or die();

use DigitalPeak\Component\DPCalendar\Administrator\Helper\Booking;
use DigitalPeak\Component\DPCalendar\Administrator\Helper\DateHelper;
use DigitalPeak\Component\DPCalendar\Administrator\Helper\DPCalendarHelper;
use DigitalPeak\Component\DPCalendar\Administrator\Translator\Translator;
use DigitalPeak\Component\DPCalendar\Site\Helper\RouteHelper;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Mail\Mail;
use Joomla\CMS\Mail\MailerFactoryAwareInterface;
use Joomla\CMS\Mail\MailerFactoryAwareTrait;
use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\User\CurrentUserInterface;
use Joomla\CMS\User\CurrentUserTrait;

class TicketController extends FormController implements MailerFactoryAwareInterface, CurrentUserInterface
{
	use MailerFactoryAwareTrait;
	use CurrentUserTrait;

	protected $text_prefix = 'COM_DPCALENDAR_TICKET';

	public function pdfdownload(): void
	{
		$model  = $this->getModel('Ticket', 'Administrator', ['ignore_request' => false]);
		$ticket = $model->getItem(['uid' => $this->input->getCmd('uid')]);

		if ($ticket == null || empty($ticket->id)) {
			throw new \Exception('JERROR_ALERTNOAUTHOR', 403);
		}

		if (Booking::createTicket($ticket, ComponentHelper::getParams('com_dpcalendar'), false) !== '' && Booking::createTicket($ticket, ComponentHelper::getParams('com_dpcalendar'), false) !== '0') {
			$this->app->close();
		} else {
			$this->app->redirect(RouteHelper::getTicketRoute($ticket));
		}
	}

	public function pdfsend(): void
	{
		$model  = $this->getModel('Ticket', 'Administrator', ['ignore_request' => false]);
		$ticket = $model->getItem(['uid' => $this->input->getCmd('uid')]);

		if ($ticket == null || empty($ticket->id)) {
			throw new \Exception('JERROR_ALERTNOAUTHOR', 403);
		}

		$this->app->getLanguage()->load('com_dpcalendar', JPATH_ADMINISTRATOR . '/components/com_dpcalendar');


		$event = $this->app->bootComponent('dpcalendar')->getMVCFactory()->createModel('Event', 'Administrator')->getItem($ticket->event_id);

		// Create the ticket details for mail notification
		$params = clone ComponentHelper::getParams('com_dpcalendar');
		$params->set('show_header', false);

		$details = DPCalendarHelper::renderLayout(
			'ticket.details',
			[
				'ticket'     => $ticket,
				'event'      => $event,
				'translator' => new Translator(),
				'dateHelper' => new DateHelper(),
				'params'     => $params
			]
		);

		$additionalVars = [
			'ticketDetails' => $details,
			'ticketLink'    => RouteHelper::getTicketRoute($ticket, true),
			'ticketUid'     => $ticket->uid,
			'sitename'      => $this->app->get('sitename'),
			'user'          => $this->getCurrentUser()->name
		];

		$subject = DPCalendarHelper::renderEvents(
			[$event],
			Text::_('COM_DPCALENDAR_TICKET_NOTIFICATION_SEND_SUBJECT'),
			null,
			$additionalVars
		);

		$body = DPCalendarHelper::renderEvents(
			[$event],
			Text::_('COM_DPCALENDAR_TICKET_NOTIFICATION_SEND_BODY'),
			null,
			$additionalVars
		);

		// Send to the ticket holder
		$mailer = $this->getMailerFactory()->createMailer();
		$mailer->setSubject($subject);
		$mailer->setBody($body);
		$mailer->addRecipient($ticket->email);
		if ($mailer instanceof Mail) {
			$mailer->IsHTML(true);
		}

		// Attache the new ticket
		$params->set('show_header', true);
		$fileName = Booking::createTicket($ticket, $params, true);
		if ($fileName !== '' && $fileName !== '0' && $fileName !== null) {
			$mailer->addAttachment($fileName);
		}

		$mailer->Send();

		if ($fileName !== null && file_exists($fileName)) {
			unlink($fileName);
		}

		$this->app->enqueueMessage(Text::_('COM_DPCALENDAR_CONTROLLER_SEND_SUCCESS'));

		$this->app->redirect(base64_decode($this->input->getBase64('return')));
	}

	public function certificatedownload(): void
	{
		$model  = $this->getModel('Ticket', 'Administrator', ['ignore_request' => false]);
		$ticket = $model->getItem(['uid' => $this->input->getCmd('uid')]);

		if (!$ticket || $ticket->id == 0) {
			throw new \Exception(Text::_('COM_DPCALENDAR_ALERT_NO_AUTH'), 403);
		}

		$certificate = Booking::createCertificate($ticket, ComponentHelper::getParams('com_dpcalendar'), false);
		// @phpstan-ignore-next-line
		if ($certificate !== null && $certificate !== '' && $certificate !== '0') {
			$this->app->close();
		} else {
			$this->app->redirect(RouteHelper::getTicketRoute($ticket));
		}
	}

	public function save($key = null, $urlVar = 't_id')
	{
		$data = $this->input->post->get('jform', [], 'array');
		if (!empty($data['id'])) {
			$item = $this->getModel()->getItem($data['id']);
			if ($item) {
				$data['catid'] = $item->event_calid;
			}
		}
		$this->input->set('jform', $data);
		return parent::save($key, $urlVar);
	}

	public function edit($key = null, $urlVar = 't_id')
	{
		return parent::edit($key, $urlVar);
	}

	public function cancel($key = 't_id')
	{
		return parent::cancel($key);
	}
}
