<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2015 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Component\DPCalendar\Site\View\Ticket;

defined('_JEXEC') or die();

use chillerlan\QRCode\QRCode;
use DigitalPeak\Component\DPCalendar\Administrator\Helper\DPCalendarHelper;
use DigitalPeak\Component\DPCalendar\Administrator\View\BaseView;
use DigitalPeak\Component\DPCalendar\Site\Helper\RouteHelper;
use Joomla\CMS\Factory;

class HtmlView extends BaseView
{
	/** @var ?object */
	protected $event;

	/** @var ?object */
	protected $booking;

	/** @var array */
	protected $ticketFields;

	/** @var string */
	protected $qrCodeString;

	/** @var object */
	protected $ticket;

	public function display($tpl = null): void
	{
		$model = Factory::getApplication()->bootComponent('dpcalendar')->getMVCFactory()->createModel('Ticket', 'Administrator');
		$this->setModel($model, true);

		parent::display($tpl);
	}

	protected function init(): void
	{
		// Nomenu rules changes the first - to a :
		$ticket = $this->getModel()->getItem(['uid' => str_replace(':', '-', $this->input->getString('uid', ''))]);
		if (!$ticket || !$ticket->id) {
			throw new \Exception($this->translate('COM_DPCALENDAR_ALERT_NO_AUTH'));
		}

		$this->event   = $this->app->bootComponent('dpcalendar')->getMVCFactory()->createModel('Event', 'Site')->getItem($ticket->event_id) ?: null;
		$this->booking = $this->app->bootComponent('dpcalendar')->getMVCFactory()->createModel('Booking', 'Administrator')
			->getItem(['id' => $ticket->booking_id, 'token' => $this->input->get('token')]) ?: null;

		if (!$this->booking || $this->booking->id == null || !$this->event) {
			throw new \Exception($this->translate('COM_DPCALENDAR_ALERT_NO_AUTH'));
		}

		// Try to find the label of the ticket type
		$ticket->price_label       = '';
		$ticket->price_description = '';
		if (($this->event->price ?? false)
			&& $ticket->price
			&& array_key_exists($ticket->type, $this->event->price->label)
			&& $this->event->price->label[$ticket->type]) {
			$ticket->price_label = $this->event->price->label[$ticket->type];

			if ($this->event->price->description[$ticket->type]) {
				$ticket->price_description = $this->event->price->description[$ticket->type];
			}
		}

		// @phpstan-ignore-next-line
		$ticket->catid = $this->event->catid;
		$ticket->text  = '';
		$this->app->triggerEvent('onContentPrepare', ['com_dpcalendar.ticket', &$ticket, &$this->params, 0]);

		$ticket->displayEvent = new \stdClass();
		$results              = $this->app->triggerEvent(
			'onContentBeforeDisplay',
			['com_dpcalendar.ticket', &$ticket, &$this->params, 0]
		);
		$ticket->displayEvent->beforeDisplayContent = trim(implode("\n", $results));

		$results = $this->app->triggerEvent(
			'onContentAfterDisplay',
			['com_dpcalendar.ticket', &$ticket, &$this->params, 0]
		);
		$ticket->displayEvent->afterDisplayContent = trim(implode("\n", $results));

		$this->ticketFields = $this->app->bootComponent('dpcalendar')->getMVCFactory()->createModel('FieldsOrder', 'Administrator')->getTicketFields($ticket, $this->params, $this->app);

		$this->qrCodeString = '';
		if ($this->params->get('ticket_show_barcode', 1)) {
			// Creating a QR code is memory intensive
			DPCalendarHelper::increaseMemoryLimit(130 * 1024 * 1024);

			$this->qrCodeString = (new QRCode())->render(RouteHelper::getTicketCheckinRoute($ticket, true));
		}

		$this->ticket = $ticket;
	}
}
