<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2014 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Component\DPCalendar\Site\View\Bookingform;

defined('_JEXEC') or die();

use DigitalPeak\Component\DPCalendar\Administrator\Helper\Booking;
use DigitalPeak\Component\DPCalendar\Administrator\View\BaseView;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\HTML\HTMLHelper;

class HtmlView extends BaseView
{
	/** @var ?\stdClass */
	protected $booking;

	/** @var string */
	protected $bookingId;

	/** @var array */
	protected $tickets;

	/** @var bool */
	protected $hasCoupons;

	/** @var ?\stdClass */
	protected $event;

	/** @var array */
	protected $events;

	/** @var Form */
	protected $form;

	/** @var string */
	protected $returnPage;

	/** @var string */
	protected $selection;

	/** @var bool */
	protected $needsPayment;

	public function display($tpl = null): void
	{
		$model = Factory::getApplication()->bootComponent('dpcalendar')->getMVCFactory()->createModel('Booking', 'Administrator');
		$this->setModel($model, true);

		parent::display($tpl);
	}

	protected function init(): void
	{
		$this->booking = $this->get('Item') ?: null;
		if (!$this->booking && $token = $this->app->getInput()->get('token')) {
			$this->booking = $this->getModel('Booking')->getItem(['token' => $token]) ?: null;
			$this->tmpl .= '&token=' . $token;
		}

		$this->bookingId  = $this->booking && !empty($this->booking->id) ? $this->booking->id : 0;
		$this->tickets    = [];
		$this->hasCoupons = false;

		if ($this->bookingId && !$this->booking->params->get('access-edit')) {
			$this->handleNoAccess();
			return;
		}

		if ($this->bookingId) {
			$this->tickets = $this->booking->tickets;
		}

		$eventId = $this->tickets ? $this->tickets[0]->event_id : $this->input->getInt('e_id', 0);
		if (!$eventId && !$this->bookingId) {
			$this->handleNoAccess();
			return;
		}

		$this->event = $this->app->bootComponent('dpcalendar')->getMVCFactory()->createModel('Event', 'Site')->getItem($eventId) ?: null;

		// If no event found, then fail
		if (!$this->event) {
			$this->handleNoAccess();
			return;
		}

		$this->events = [];
		try {
			$this->events = $this->event->booking_series != 2 ? [] : Booking::getSeriesEvents($this->event);
		} catch (\Exception $exception) {
			if ((JDEBUG || $this->user->authorise('dpcalendar.admin.book', 'com_dpcalendar.category.' . $this->event->catid))
				&& $exception->getCode() == 1) {
				$this->app->enqueueMessage('Too many series events, disabling the book series function!', 'warning');
			}
		}

		// If no series events are found add the single event
		if ($this->events === []) {
			$this->events = [$this->event];
		}

		// When the original event has instances choosing set then load the work on the first instance
		if ($this->event->booking_series == 2 && $this->event->original_id == -1) {
			unset($this->events[$this->event->id]);
			$this->event = reset($this->events);
		}

		if ($this->event && !$this->event->payment_provider) {
			$this->event->payment_provider = 0;
		}

		$this->form = $this->getModel('Booking')->getForm(['event_id' => [$this->event->id => []]]);
		$this->form->removeField('processor');
		$this->form->setFieldAttribute('user_id', 'type', 'hidden');
		$this->form->setFieldAttribute('id', 'type', 'hidden');

		$this->returnPage = $this->get('ReturnPage');

		if ($this->bookingId) {
			$this->form->bind($this->booking);

			$this->form->setFieldAttribute('tax', 'disabled', 'true');
			$this->form->setFieldAttribute('tax_rate', 'disabled', 'true');
			$this->form->setFieldAttribute('coupon_id', 'disabled', 'true');
			$this->form->setFieldAttribute('coupon_rate', 'disabled', 'true');
		} else {
			$this->form->removeField('latitude');
			$this->form->removeField('longitude');
			$this->form->removeField('price');
			$this->form->removeField('tax');
			$this->form->removeField('tax_rate');
			$this->form->removeField('coupon_rate');
			$this->form->removeField('state');
		}

		$this->selection = $this->app->getUserState('com_dpcalendar.booking.form.tickets', []);

		if ($this->bookingId) {
			return;
		}

		// Check if payment is needed
		$this->needsPayment = Booking::paymentRequired($this->event);
		foreach ($this->events as $s) {
			if (Booking::paymentRequired($s)) {
				$this->needsPayment = true;
				break;
			}
		}

		$this->hasCoupons = false;
		if ($this->needsPayment) {
			// Setup coupon stuff
			$model = $this->app->bootComponent('dpcalendar')->getMVCFactory()->createModel('Coupons', 'Administrator', ['ignore_request' => true]);
			$model->setState('filter.state', 1);

			foreach ($model->getItems() as $coupon) {
				if (!$coupon->calendars) {
					$this->hasCoupons = true;
					break;
				}

				$calendars = explode(',', (string)$coupon->calendars);
				foreach ($this->events as $e) {
					if (in_array($e->catid, $calendars)) {
						$this->hasCoupons = true;
						break;
					}
				}
			}
		}

		$this->form->setValue('coupon_id', null, null);

		$hasTickets = false;
		foreach ($this->events as $event) {
			// Normalize the price field
			if (!$event->price || !$event->price->value) {
				$event->price = (object)['value' => ['0'], 'label' => [$this->translate('COM_DPCALENDAR_TICKET')], 'description' => ['']];
			}

			// Set the ticket count
			$event->ticket_count = $event->max_tickets ?: 1;

			// Remove the already booked tickets from the ticket count
			$event->tickets = empty($event->tickets) ? $this->app->bootComponent('dpcalendar')->getMVCFactory()->createModel('Event', 'Administrator')->getTickets($event->id) : $event->tickets;
			foreach ($event->tickets as $ticket) {
				if ($ticket->email == $this->user->email || ($ticket->user_id && $ticket->user_id == $this->user->id)) {
					$event->ticket_count--;
				}
			}

			// If ticket count is higher than available space, reduce it
			if ($event->capacity !== null && $event->ticket_count > ($event->capacity - $event->capacity_used) && !$event->booking_waiting_list) {
				$event->ticket_count = $event->capacity - $event->capacity_used;
			}

			// Ticket count must be at least 0
			$event->ticket_count = max($event->ticket_count, 0);

			if ($event->ticket_count) {
				$hasTickets = true;
			}

			$event->booking_information = $event->booking_information ? HTMLHelper::_('content.prepare', $event->booking_information) : '';
		}

		if (!$hasTickets) {
			$this->app->enqueueMessage($this->translate('COM_DPCALENDAR_VIEW_BOOKINGFORM_CHOOSE_TICKET_LIMIT_REACHED'), 'warning');
			$this->app->redirect(base64_decode((string)$this->returnPage));
		}
	}
}
