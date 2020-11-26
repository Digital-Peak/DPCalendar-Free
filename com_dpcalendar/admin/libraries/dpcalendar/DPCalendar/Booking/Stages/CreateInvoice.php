<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2019 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */
namespace DPCalendar\Booking\Stages;

defined('_JEXEC') or die();

use DPCalendar\Helper\DateHelper;
use DPCalendar\Helper\DPCalendarHelper;
use DPCalendar\Translator\Translator;
use League\Pipeline\StageInterface;

class CreateInvoice implements StageInterface
{
	/**
	 * @var \JApplicationCms
	 */
	private $application = null;

	/**
	 * @var \DPCalendarTableBooking
	 */
	private $bookingTable = null;

	/**
	 * @var \DPCalendarModelCountry
	 */
	private $model;

	public function __construct(\JApplicationCms $application, \DPCalendarTableBooking $bookingTable, \DPCalendarModelCountry $model)
	{
		$this->application  = $application;
		$this->bookingTable = $bookingTable;
		$this->model        = $model;
	}

	public function __invoke($payload)
	{
		// Do not generate an invoice when it existed and was active already
		if ($payload->item->invoice && ($payload->oldItem && $payload->oldItem->state == 1)) {
			return $payload;
		}

		$this->application->getLanguage()->load('com_dpcalendar', JPATH_ADMINISTRATOR . '/components/com_dpcalendar');
		$this->application->getLanguage()->load('com_dpcalendar.countries', JPATH_ADMINISTRATOR . '/components/com_dpcalendar');
		$translator = new Translator();

		$booking = clone $payload->item;
		if (!empty($booking->country_code)) {
			$booking->country = $translator->translate('COM_DPCALENDAR_COUNTRY_' . $booking->country_code);
		} else if (!empty($booking->country)) {
			$booking->country = $translator->translate('COM_DPCALENDAR_COUNTRY_' . $this->model->getItem($booking->country)->short_code);
		}

		$params = clone \JComponentHelper::getParams('com_dpcalendar');

		$details = DPCalendarHelper::renderLayout(
			'booking.invoice',
			[
				'booking'    => $booking,
				'tickets'    => $payload->tickets,
				'translator' => $translator,
				'dateHelper' => new DateHelper(),
				'params'     => $params
			]
		);

		$this->bookingTable->load($booking->id);
		$this->bookingTable->bind(['invoice' => $details]);
		$this->bookingTable->store();
		$payload->item->invoice = $details;

		return $payload;
	}
}
