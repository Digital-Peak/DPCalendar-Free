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
use Joomla\CMS\Application\CMSApplication;
use Joomla\Registry\Registry;
use League\Pipeline\StageInterface;

class CreateInvoice implements StageInterface
{
	/**
	 * @var CMSApplication
	 */
	private $application;

	/**
	 * @var \DPCalendarTableBooking
	 */
	private $bookingTable;

	/**
	 * @var \DPCalendarModelCountry
	 */
	private $model;

	/**
	 * @var Registry
	 */
	private $params;

	public function __construct(
		CMSApplication $application,
		\DPCalendarTableBooking $bookingTable,
		\DPCalendarModelCountry $model,
		Registry $params
	) {
		$this->application  = $application;
		$this->bookingTable = $bookingTable;
		$this->model        = $model;
		$this->params       = $params;
	}

	public function __invoke($payload)
	{
		// Do not generate an invoice when it existed and was active already
		if ($payload->item->invoice && ($payload->oldItem && $payload->oldItem->state == 1)) {
			return $payload;
		}

		$payload->language->load('com_dpcalendar.countries', JPATH_ADMINISTRATOR . '/components/com_dpcalendar');
		$translator = new Translator($payload->language);

		$booking = clone $payload->item;
		if (!empty($booking->country_code)) {
			$booking->country = $translator->translate('COM_DPCALENDAR_COUNTRY_' . $booking->country_code);
		} else if (!empty($booking->country)) {
			$booking->country = $translator->translate('COM_DPCALENDAR_COUNTRY_' . $this->model->getItem($booking->country)->short_code);
		}


		$details = DPCalendarHelper::renderLayout(
			'booking.invoice',
			[
				'booking'    => $booking,
				'tickets'    => $payload->tickets,
				'translator' => $translator,
				'dateHelper' => new DateHelper(),
				'params'     => $this->params
			]
		);

		$this->bookingTable->load($booking->id);
		$this->bookingTable->bind(['invoice' => $details]);
		$this->bookingTable->store();
		$payload->item->invoice = $details;

		return $payload;
	}
}
