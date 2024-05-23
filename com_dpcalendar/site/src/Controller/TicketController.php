<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2015 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Component\DPCalendar\Site\Controller;

defined('_JEXEC') or die();

use DigitalPeak\Component\DPCalendar\Administrator\Helper\Booking;
use DigitalPeak\Component\DPCalendar\Site\Helper\RouteHelper;
use Joomla\CMS\Application\CMSWebApplicationInterface;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\User\CurrentUserInterface;
use Joomla\CMS\User\CurrentUserTrait;

class TicketController extends BaseController implements CurrentUserInterface
{
	use CurrentUserTrait;

	public function checkin(): bool
	{
		$ticket = $this->getModel()->getItem(['uid' => $this->input->getCmd('uid')]);
		if (!$ticket) {
			$this->setRedirect(Uri::base());

			return true;
		}

		$this->setRedirect(RouteHelper::getTicketRoute($ticket));

		$user    = $this->getCurrentUser();
		$event   = $this->getModel('Event')->getItem($ticket->event_id);
		$booking = $this->getModel('Booking')->getItem($ticket->booking_id);

		if ($event === false || $booking === false) {
			$this->setMessage(Text::_('COM_DPCALENDAR_VIEW_TICKET_NO_CHECK_IN_PERMISSION'), 'error');

			return true;
		}

		if ($event->created_by != $user->id
			&& !$user->authorise('dpcalendar.admin.book', 'com_dpcalendar.category.' . $event->catid)) {
			$this->setMessage(Text::_('COM_DPCALENDAR_VIEW_TICKET_NO_CHECK_IN_PERMISSION'), 'error');

			return true;
		}

		if ($booking->state != 1) {
			$this->setMessage(
				Text::sprintf('COM_DPCALENDAR_VIEW_TICKET_BOOKING_NOT_ACTIVE', Booking::getStatusLabel($booking)),
				'error'
			);

			return true;
		}

		if ($ticket->state == 9) {
			$this->setMessage(Text::_('COM_DPCALENDAR_VIEW_TICKET_CHECKED_IN'), 'error');

			return true;
		}

		$model = $this->getModel();
		// Do not check in original events
		//@todo do that properly
		if ($event->original_id != -1 && !$model->publish($ticket->id, 9)) {
			$this->setMessage($model->getError(), 'error');

			return true;
		}

		$this->setMessage(Text::_('COM_DPCALENDAR_VIEW_TICKET_CHECKED_IN'));

		return true;
	}

	public function pdfdownload(): void
	{
		$model  = $this->getModel('Ticket', 'Administrator', ['ignore_request' => false]);
		$ticket = $model->getItem(['uid' => $this->input->getCmd('uid')]);

		if ($ticket == null) {
			throw new \Exception(Text::_('COM_DPCALENDAR_ALERT_NO_AUTH'), 403);
		}


		$booking = $this->getModel('Booking', 'Administrator', ['ignore_request' => false])->getItem($ticket->booking_id);
		if (!$booking && $token = $this->input->get('token')) {
			$booking = $this->getModel('Booking', 'Administrator', ['ignore_request' => false])->getItem(['token' => $token]);
		}

		if ($booking == null) {
			throw new \Exception(Text::_('COM_DPCALENDAR_ALERT_NO_AUTH'), 403);
		}

		$fileName = Booking::createTicket($ticket, ComponentHelper::getParams('com_dpcalendar'), false);
		if ($fileName !== '' && $fileName !== '0') {
			$this->app->close();
		} elseif ($this->app instanceof CMSWebApplicationInterface) {
			$this->app->redirect(RouteHelper::getTicketRoute($ticket));
		}
	}

	public function certificatedownload(): void
	{
		$model  = $this->getModel('Ticket', 'Administrator', ['ignore_request' => false]);
		$ticket = $model->getItem(['uid' => $this->input->getCmd('uid')]);

		if ($ticket === false) {
			throw new \Exception(Text::_('COM_DPCALENDAR_ALERT_NO_AUTH'), 403);
		}

		$booking = $this->getModel('Booking', 'Administrator', ['ignore_request' => false])->getItem($ticket->booking_id);
		if (!$booking && $token = $this->input->get('token')) {
			$booking = $this->getModel('Booking', 'Administrator', ['ignore_request' => false])->getItem(['token' => $token]);
		}

		if ($booking == null) {
			throw new \Exception(Text::_('COM_DPCALENDAR_ALERT_NO_AUTH'), 403);
		}

		$fileName = Booking::createCertificate($ticket, ComponentHelper::getParams('com_dpcalendar'), false, $booking);
		if ($fileName !== '' && $fileName !== '0' && $fileName !== null) {
			$this->app->close();
		} elseif ($this->app instanceof CMSWebApplicationInterface) {
			$this->app->redirect(RouteHelper::getTicketRoute($ticket));
		}
	}

	public function getModel($name = 'Ticket', $prefix = 'Administrator', $config = ['ignore_request' => true])
	{
		return parent::getModel($name, $prefix, $config);
	}
}
