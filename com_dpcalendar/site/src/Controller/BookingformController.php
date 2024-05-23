<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2014 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Component\DPCalendar\Site\Controller;

defined('_JEXEC') or die();

use DigitalPeak\Component\DPCalendar\Administrator\Helper\Booking;
use DigitalPeak\Component\DPCalendar\Administrator\Helper\DPCalendarHelper;
use DigitalPeak\Component\DPCalendar\Site\Helper\RouteHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;

class BookingformController extends FormController
{
	protected $view_item   = 'bookingform';
	protected $text_prefix = 'COM_DPCALENDAR_VIEW_BOOKING';

	public function add(): bool
	{
		if (!$this->allowAdd()) {
			$this->setRedirect($this->getReturnPage(), $this->message ?: Text::_('COM_DPCALENDAR_ALERT_NO_AUTH'), 'warning');

			return false;
		}

		if (!parent::add()) {
			$this->setRedirect($this->getReturnPage());

			return false;
		}

		$this->setRedirect(
			Route::_('index.php?option=' . $this->option . '&view=' . $this->view_item . $this->getRedirectToItemAppend(), false)
		);

		return true;
	}

	public function canAdd(array $data): bool
	{
		return $this->allowAdd($data);
	}

	protected function allowAdd($data = []): bool
	{
		if (!isset($data['event_id']) && !$this->input->getInt('e_id', 0)) {
			return false;
		}

		if (!isset($data['event_id'])) {
			$data['event_id'] = [$this->input->getInt('e_id', 0) => []];
		}

		$found = false;
		foreach ($data['event_id'] as $id => $prices) {
			$event = $this->getModel('Booking')->getEvent($id);
			if ($event == null || !$event->id) {
				continue;
			}

			// If the whole series needs to be booked, then we need to check against the original event
			if ($event->original_id > 0 && $event->booking_series == 1) {
				$event = $this->getModel('Booking')->getEvent((string)$event->original_id);
			}

			if ($event == null || !$event->id) {
				continue;
			}

			$this->input->set('e_id', $event->id);

			if (Booking::openForBooking($event)) {
				$found = true;
				continue;
			}

			if (DPCalendarHelper::getDate($event->start_date)->format('U') < DPCalendarHelper::getDate()->format('U')) {
				$this->setMessage(Text::_('COM_DPCALENDAR_BOOK_ERROR_PAST'), 'warning');
				continue;
			}
			if ($event->capacity === null) {
				continue;
			}
			if ($event->capacity_used < $event->capacity) {
				continue;
			}
			if ($event->booking_waiting_list) {
				continue;
			}
			$this->setMessage(Text::_('COM_DPCALENDAR_BOOK_ERROR_CAPACITY_EXHAUSTED'), 'warning');
		}

		return $found;
	}

	protected function allowEdit($data = [], $key = 'id')
	{
		$recordId = $data[$key] ?? 0;
		$booking  = $this->getModel('Booking')->getItem($recordId);

		if (!$booking && $token = $this->app->getInput()->get('token')) {
			$booking = $this->getModel('Booking')->getItem(['token' => $token]);
		}

		if (!$booking) {
			return false;
		}

		// Active or unpublished bookings can't be edited
		return $booking->params->get('access-edit');
	}

	protected function allowDelete(array $data = [], string $key = 'id'): bool
	{
		$recordId = $data[$key] ?? 0;
		$booking  = $this->getModel('Booking')->getItem($recordId);

		if (!$booking && $token = $this->app->getInput()->get('token')) {
			$booking = $this->getModel('Booking')->getItem(['token' => $token]);
		}

		if (!$booking) {
			return false;
		}

		return (bool)$booking->params->get('access-delete');
	}

	public function edit($key = 'id', $urlVar = 'b_id')
	{
		$this->input->set('layout', 'edit');

		return parent::edit($key, $urlVar);
	}

	public function cancel($key = 'b_id')
	{
		$return = parent::cancel($key);

		// Redirect to the return page.
		$this->setRedirect(Route::_($this->getReturnPage(), false));

		return $return;
	}

	public function delete(?string $key = 'b_id'): bool
	{
		$recordId = $this->input->get($key !== null && $key !== '' && $key !== '0' ? $key : '');

		if (!$this->allowDelete([$key => $recordId], $key !== null && $key !== '' && $key !== '0' ? $key : '')) {
			throw new \Exception(Text::_('JLIB_APPLICATION_ERROR_EDIT_NOT_PERMITTED'));
		}

		$this->getModel('Booking')->delete($recordId);
		$this->setRedirect(Route::_($this->getReturnPage()), Text::_('COM_DPCALENDAR_BOOK_DELETE_SUCCESS'));

		return true;
	}

	public function save($key = null, $urlVar = 'b_id')
	{
		$isNew  = !$this->input->getInt($urlVar, 0);
		$result = parent::save($key, $urlVar);
		if ($result) {
			$booking = $this->getModel('Booking')->getItem($this->input->getInt('b_id', 0));
			if (!$booking && $token = $this->app->getInput()->get('token')) {
				$booking = $this->getModel('Booking')->getItem(['token' => $token]);
			}
			if (!$booking) {
				throw new \Exception('Booking not found');
			}

			$layout = 'review';
			switch ($booking->state) {
				case 2:
					$layout = 'confirm';
					break;
				case 3:
					$layout = 'order';
					break;
				case 8:
					$layout = '';
			}

			$this->setRedirect(RouteHelper::getBookingRoute($booking) . ($isNew && $layout ? '&layout=' . $layout : ''));
		}

		$data = $this->input->post->get('jform', [], 'array');
		if (array_key_exists('event_id', $data)) {
			$this->app->setUserState('com_dpcalendar.booking.form.tickets', $data['event_id']);
		}

		// Reset the show submit, because we have a proper thank you page anyway
		if ($this->messageType == 'message') {
			$this->message = '';
		}

		return $result;
	}

	public function getModel($name = 'Booking', $prefix = 'Administrator', $config = ['ignore_request' => true])
	{
		return parent::getModel($name, $prefix, $config);
	}

	protected function getRedirectToItemAppend($recordId = null, $urlVar = ''): string
	{
		$append = parent::getRedirectToItemAppend($recordId, $urlVar);
		$itemId = $this->input->getInt('Itemid', 0);
		$return = $this->getReturnPage();

		if ($itemId !== 0) {
			$append .= '&Itemid=' . $itemId;
		}

		if ($return !== '' && $return !== '0') {
			$append .= '&return=' . base64_encode($return);
		}

		$append .= '&e_id=' . $this->input->getInt('e_id', 0);

		$tmpl = $this->input->get('tmpl', '');
		if ($tmpl !== '') {
			$append .= '&tmpl=' . $tmpl;
		}

		if ($token = $this->input->get('token')) {
			$append .= '&token=' . $token;
		}

		return $append;
	}

	protected function getReturnPage(): string
	{
		$return = $this->input->get('return', null, 'base64');

		if (empty($return) || !Uri::isInternal(base64_decode((string)$return))) {
			return Uri::base();
		}

		return base64_decode((string)$return);
	}
}
