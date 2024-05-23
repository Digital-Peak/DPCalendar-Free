<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2015 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Component\DPCalendar\Site\Controller;

defined('_JEXEC') or die();

use DigitalPeak\Component\DPCalendar\Administrator\Booking\Stages\CollectEventsAndTickets;
use DigitalPeak\Component\DPCalendar\Administrator\Booking\Stages\SetupForNew;
use DigitalPeak\Component\DPCalendar\Administrator\Helper\Booking;
use DigitalPeak\Component\DPCalendar\Administrator\Helper\DPCalendarHelper;
use DigitalPeak\Component\DPCalendar\Administrator\Model\BookingModel;
use DigitalPeak\Component\DPCalendar\Site\Helper\RouteHelper;
use Joomla\CMS\Application\CMSWebApplicationInterface;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\User\CurrentUserInterface;
use Joomla\CMS\User\CurrentUserTrait;
use League\Pipeline\PipelineBuilder;

class BookingController extends BaseController implements CurrentUserInterface
{
	use CurrentUserTrait;

	public function invite(): void
	{
		$model = $this->getModel();

		$booking = $model->getItem(['uid' => $this->input->getString('uid', '')]);
		if (!$booking && $token = $this->input->get('token')) {
			$booking = $model->getItem(['token' => $token]);
		}

		if (!$booking) {
			throw new \Exception('Booking not found');
		}

		if ($this->input->getInt('accept', 0) !== 0) {
			$booking->state = $booking->price > 0 ? 3 : 1;
			$model->save((array)json_decode(json_encode($booking) ?: ''));
			$this->setRedirect(RouteHelper::getBookingRoute($booking));
		} else {
			$model->delete($booking->id);
			$this->setRedirect(Uri::base());
		}
	}

	public function calculateprice(): void
	{
		Session::checkToken() or jexit(Text::_('JINVALID_TOKEN'));

		$payload       = new \stdClass();
		$payload->data = $this->input->get('jform', [], 'array');
		$builder       = new PipelineBuilder();
		$builder->add(
			new CollectEventsAndTickets(
				$this->app,
				$this->app->bootComponent('dpcalendar')->getMVCFactory()->createModel('Booking', 'Administrator', ['ignore_request' => true])
			)
		);
		$builder->add(new SetupForNew(
			$this->app,
			$this->getCurrentUser(),
			$this->app->bootComponent('dpcalendar')->getMVCFactory()->createModel('Taxrate', 'Administrator', ['ignore_request' => true]),
			$this->app->bootComponent('dpcalendar')->getMVCFactory()->createModel('Coupon', 'Administrator', ['ignore_request' => true]),
			ComponentHelper::getParams('com_dpcalendar'),
			true
		));

		try {
			$builder->build()($payload);
		} catch (\Exception) {
		}

		$data               = [];
		$data['events']     = $payload->data['price_details'];
		$data['total']      = DPCalendarHelper::renderPrice(number_format($payload->data['price'], 2, '.', ''));
		$data['coupon']     = $payload->data['coupon_rate'] ?? 0;
		$data['coupon']     = $data['coupon'] ? DPCalendarHelper::renderPrice(number_format($data['coupon'], 2, '.', '')) : 0;
		$data['couponarea'] = $payload->data['coupon_area'] ?? 0;
		$data['currency']   = DPCalendarHelper::getComponentParameter('currency_symbol', '$');

		$data['taxtitle']     = $payload->data['tax_title'] ?? '';
		$data['tax']          = empty($payload->data['tax']) ? 0 : DPCalendarHelper::renderPrice(number_format($payload->data['tax'], 2, '.', ''));
		$data['taxrate']      = $payload->data['tax_rate'] ?? 0;
		$data['taxinclusive'] = $payload->data['tax_inclusive'] ?? 0;

		DPCalendarHelper::sendMessage('', false, $data);
	}

	public function invoice(): void
	{
		$model = $this->getModel('Booking', 'Administrator', ['ignore_request' => false]);

		$booking = $model->getItem();
		if (!$booking && $token = $this->input->get('token')) {
			$booking = $model->getItem(['token' => $token]);
		}

		if ($booking == null) {
			throw new \Exception(Text::_('COM_DPCALENDAR_ALERT_NO_AUTH'), 403);
		}

		$fileName = Booking::getInvoice($booking);
		if ($fileName !== '' && $fileName !== '0') {
			$this->app->close();
		} elseif ($this->app instanceof CMSWebApplicationInterface) {
			$this->app->redirect(RouteHelper::getBookingRoute($booking));
		}
	}

	public function receipt(): void
	{
		$model = $this->getModel('Booking', 'Administrator', ['ignore_request' => false]);
		$state = $model->getState();

		$booking = $model->getItem();
		if (!$booking && $token = $this->input->get('token')) {
			$booking = $model->getItem(['token' => $token]);
		}

		if ($booking == null) {
			throw new \Exception(Text::_('COM_DPCALENDAR_ALERT_NO_AUTH'), 403);
		}

		$fileName = Booking::createReceipt($booking, $booking->tickets, $state->params);
		if ($fileName !== '' && $fileName !== '0') {
			$this->app->close();
		} elseif ($this->app instanceof CMSWebApplicationInterface) {
			$this->app->redirect(RouteHelper::getBookingRoute($booking));
		}
	}

	public function confirm(): bool
	{
		$model = $this->getModel('Booking', 'Administrator', ['ignore_request' => false]);
		$model->getState();

		$booking = $model->getItem();
		if (!$booking && $token = $this->input->get('token')) {
			$booking = $model->getItem(['token' => $token]);
		}

		if ($booking == null) {
			throw new \Exception(Text::_('COM_DPCALENDAR_ALERT_NO_AUTH'), 403);
		}

		$booking->processor = $this->input->get('payment_provider');
		$booking->state     = $booking->price ? 3 : 1;

		$this->getModel()->save((array)json_decode(json_encode($booking) ?: ''));

		$this->setRedirect(RouteHelper::getBookingRoute($booking) . '&layout=' . ($booking->price ? 'pay' : 'order'));

		return true;
	}

	public function review(): bool
	{
		$app = $this->app;

		/** @var BookingModel $model */
		$model = $this->getModel('Booking', 'Administrator', ['ignore_request' => false]);
		$model->getState();

		$booking = $model->getItem();
		if (!$booking && $token = $this->input->get('token')) {
			$booking = $model->getItem(['token' => $token]);
		}

		if (!$booking || !$booking->id) {
			throw new \Exception(Text::_('COM_DPCALENDAR_ALERT_NO_AUTH'), 403);
		}

		$booking->state = 2;

		$eventIds = array_unique(array_map(static fn ($t) => $t->event_id, $booking->tickets));

		$event = count($eventIds) == 1 ? $model->getEvent($eventIds[0]) : null;
		if ($event && ($event->booking_series != 2 || !$event->rrule)
			&& $event->capacity != null && $event->capacity_used >= $event->capacity && $event->booking_waiting_list) {
			$booking->state = 8;
		}

		// Check for validation errors
		$failedTickets = [];
		foreach ($this->input->getArray() as $name => $value) {
			if (!str_starts_with((string)$name, 'ticket-')) {
				continue;
			}

			$value['state'] = $booking->state;
			$model          = $this->getModel('Ticket');

			if ($model->validate($model->getForm($value, true), $value) !== false) {
				continue;
			}

			// @phpstan-ignore-next-line
			foreach ($model->getErrors() as $error) {
				$app->enqueueMessage($error instanceof \Exception ? $error->getMessage() : $error, 'warning');
			}

			$failedTickets[$value['id']] = $value;
		}

		if ($app instanceof CMSWebApplicationInterface) {
			$app->setUserState('com_dpcalendar.booking.ticket.reviewdata', $failedTickets);
		}

		if ($failedTickets !== []) {
			$this->setRedirect(RouteHelper::getBookingRoute($booking) . '&layout=review');

			return false;
		}

		$counter = 0;
		foreach ($this->input->getArray() as $name => $value) {
			if (!str_starts_with((string)$name, 'ticket-')) {
				continue;
			}

			$value['state'] = $booking->state;
			$this->getModel('Ticket')->save($value, false);
			$counter++;
		}

		// This is needed to send out the notification
		if ($booking->state === 8) {
			$this->getModel()->save((array)json_decode(json_encode($booking) ?: ''));
		} else {
			$t = $this->getModel()->getTable();
			$t->bind($booking);
			$t->store();
		}

		$this->setMessage(Text::plural('COM_DPCALENDAR_TICKETS_SAVE_SUCCESS', $counter));

		if (!$booking->price && !DPCalendarHelper::getComponentParameter('booking_confirm_step', 1)) {
			$this->confirm();
			return true;
		}

		$this->setRedirect(RouteHelper::getBookingRoute($booking) . ($booking->state == 2 ? '&layout=confirm' : ''));

		return true;
	}

	public function pay(): void
	{
		$booking = $this->getModel()->getItem($this->input->getInt('b_id', 0));

		if (!$booking && $token = $this->input->get('token')) {
			$booking = $this->getModel()->getItem(['token' => $token]);
		}

		if (!$booking || !$booking->id) {
			throw new \Exception(Text::_('COM_DPCALENDAR_ALERT_NO_AUTH'));
		}

		$rawDataPost = $this->input->post->getArray();
		$rawDataGet  = $this->input->get->getArray();
		$data        = array_merge($rawDataGet, $rawDataPost);

		/*
		 * Some plugins result in an empty Itemid being added to the request
		 * data, screwing up the payment callback validation in some cases (e.g.
		 * PayPal).
		 */
		if (array_key_exists('Itemid', $data) && empty($data['Itemid'])) {
			unset($data['Itemid']);
		}

		PluginHelper::importPlugin('dpcalendarpay');

		$this->app->triggerEvent('onDPPaymentCallBack', [$booking, $data]);

		if ($this->app instanceof CMSWebApplicationInterface) {
			$this->app->redirect(RouteHelper::getBookingRoute($booking) . '&layout=order');
		}
	}

	public function cancel(): void
	{
		$token   = $this->input->get('token');
		$booking = $this->getModel()->getItem($this->input->getInt('b_id', 0));
		if (!$booking && $token) {
			$booking = $this->getModel()->getItem(['token' => $token]);
		}

		if (!$booking || !$booking->id) {
			throw new \Exception(Text::_('COM_DPCALENDAR_ALERT_NO_AUTH'));
		}

		if (!$this->getModel()->publish($booking->id, 6, $token)) {
			throw new \Exception(Text::_('COM_DPCALENDAR_ALERT_NO_AUTH'));
		}

		$this->setRedirect(RouteHelper::getBookingRoute($booking) . '&layout=cancel');
	}

	public function paycancel(): void
	{
		$booking = $this->abort();

		$this->setRedirect(RouteHelper::getBookingRoute($booking) . '&layout=cancel');
	}

	/**
	 * @return \stdClass
	 */
	public function abort()
	{
		$booking = $this->getModel()->getItem($this->input->getInt('b_id', 0));
		if (!$booking && $token = $this->input->get('token')) {
			$booking = $this->getModel()->getItem(['token' => $token]);
		}

		if (!$booking || !$booking->id) {
			throw new \Exception(Text::_('COM_DPCALENDAR_ALERT_NO_AUTH'));
		}

		if (!$this->getModel()->delete($booking->id)) {
			throw new \Exception(Text::_('COM_DPCALENDAR_ALERT_NO_AUTH'));
		}

		$this->setRedirect(RouteHelper::getBookingRoute($booking) . '&layout=abort');

		return $booking;
	}

	public function getModel($name = 'Booking', $prefix = 'Administrator', $config = ['ignore_request' => true])
	{
		return parent::getModel($name, $prefix, $config);
	}
}
