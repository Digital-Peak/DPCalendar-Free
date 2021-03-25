<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2014 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */
namespace DPCalendar\Helper;

defined('_JEXEC') or die();

use DPCalendar\TCPDF\DPCalendar;
use DPCalendar\Translator\Translator;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Language;
use Joomla\Registry\Registry;

\JLoader::import('joomla.application.component.helper');
\JTable::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_dpcalendar/tables');

class Booking
{

	/**
	 * Creates a PDF for the given booking and tickets.
	 * If to file is set, then the PDF will be written to a file and the file
	 * name is returned. Otherwise it will be offered as download.
	 *
	 * @param \stdClass $booking
	 * @param \stdClass $tickets
	 * @param Registry  $params
	 * @param string    $toFile
	 * @param Language  $language
	 *
	 * @return string
	 */
	public static function createInvoice($booking, $tickets, $params, $toFile = false, $language = null)
	{
		try {
			$details = $booking->invoice;

			if (!$details) {
				if ($language == null) {
					$language = Factory::getLanguage();
				}
				$language->load('com_dpcalendar', JPATH_ADMINISTRATOR . '/components/com_dpcalendar');
				$details = \DPCalendarHelper::renderLayout(
					'booking.invoice',
					[
						'booking'    => $booking,
						'tickets'    => $tickets,
						'translator' => new Translator($language),
						'dateHelper' => new DateHelper(),
						'params'     => $params
					]
				);
			}

			// Disable notices (TCPDF is causing many of these)
			error_reporting(E_ALL ^ E_NOTICE);

			$pdf = new DPCalendar($params);

			// set document information
			$pdf->SetCreator(PDF_CREATOR);
			$pdf->SetAuthor('DPCalendar by joomla.digital-peak.com');
			$pdf->SetTitle('');
			$pdf->SetSubject('DPCalendar Invoice');
			$pdf->SetKeywords('Invoice, DPCalendar, Digital Peak');

			// remove default header/footer
			$pdf->setPrintHeader(true);
			$pdf->setPrintFooter(true);

			// set margins
			$pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
			$pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
			$pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

			// Adding the content
			$pdf->AddPage();
			$pdf->writeHTML($details, true, false, true, false, '');

			$fileName = $booking->uid . '.pdf';
			if ($toFile) {
				$fileName = JPATH_ROOT . '/tmp/' . $fileName;
				\JFile::delete($fileName);
			}
			ob_end_clean();
			$pdf->Output($fileName, $toFile ? 'F' : 'D');

			return $fileName;
		} catch (\Exception $e) {
			\JFactory::getApplication()->enqueueMessage($e->getMessage(), 'warning');

			return null;
		}
	}

	/**
	 * Creates a PDF for the given ticket.
	 * If to file is set, then the PDF will be written to a file and the file
	 * name is returned. Otherwise it will be offered as download.
	 *
	 * @param stdClass $ticket
	 * @param Registry $params
	 * @param string   $toFile
	 *
	 * @return string
	 */
	public static function createTicket($ticket, $params, $toFile = false)
	{
		try {
			\JFactory::getLanguage()->load('com_dpcalendar', JPATH_ADMINISTRATOR . '/components/com_dpcalendar');

			\DPCalendarHelper::increaseMemoryLimit(130 * 1024 * 1024);

			\JModelLegacy::addIncludePath(JPATH_SITE . '/components/com_dpcalendar/models');
			$model = \JModelLegacy::getInstance('Event', 'DPCalendarModel', ['ignore_request' => true]);
			$event = $model->getItem($ticket->event_id);

			$details = \DPCalendarHelper::renderLayout(
				'ticket.details',
				[
					'ticket'     => $ticket,
					'event'      => $event,
					'translator' => new Translator(),
					'dateHelper' => new DateHelper(),
					'params'     => $params
				]
			);

			// Disable notices (TCPDF is causing many of these)
			error_reporting(E_ALL ^ E_NOTICE);

			$pdf = new DPCalendar($params);

			// set document information
			$pdf->SetCreator(PDF_CREATOR);
			$pdf->SetAuthor('DPCalendar by joomla.digital-peak.com');
			$pdf->SetTitle($event->title);
			$pdf->SetSubject('DPCalendar Ticket');
			$pdf->SetKeywords('Invoice, DPCalendar, Digital Peak');

			// remove default header/footer
			$pdf->setPrintHeader(true);
			$pdf->setPrintFooter(true);

			// set margins
			$pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
			$pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
			$pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

			// Adding the content
			$pdf->AddPage();
			$pdf->writeHTML($details, true, false, true, false, '');

			if ($params->get('ticket_show_barcode', 1)) {
				$style = [
					'border'        => 2,
					'position'      => 'C',
					'vpadding'      => 'auto',
					'hpadding'      => 'auto',
					'fgcolor'       => [0, 0, 0],
					'bgcolor'       => false,
					'module_width'  => 1,
					'module_height' => 1
				];
				$pdf->write2DBarcode(\DPCalendarHelperRoute::getTicketCheckinRoute($ticket, true), 'QRCODE,L', 20, 200, 50, 50, $style, 'N');
			}

			$fileName = $ticket->uid . '.pdf';
			if ($toFile) {
				$fileName = JPATH_ROOT . '/tmp/' . $fileName;
				\JFile::delete($fileName);
			}
			$pdf->Output($fileName, $toFile ? 'F' : 'D');

			return $fileName;
		} catch (\Exception $e) {
			\JFactory::getApplication()->enqueueMessage($e->getMessage(), 'warning');

			return null;
		}
	}

	/**
	 * Returns the series events. If there are more than the given limit
	 * an exception is thrown.
	 *
	 * @param $event
	 * @param $limit
	 *
	 * @return array
	 * @throws \Exception
	 */
	public static function getSeriesEvents($event, $limit = 40)
	{
		if (!$event) {
			return [];
		}

		$events = [$event->id => $event];
		if ($event->original_id != '0') {
			$model = \JModelLegacy::getInstance('Events', 'DPCalendarModel', ['ignore_request' => true]);
			if (!$model) {
				$model = \JModelLegacy::getInstance('AdminEvents', 'DPCalendarModel', ['ignore_request' => true]);
			}

			$model->getState();
			$model->setState('filter.children', $event->original_id == -1 ? $event->id : $event->original_id);
			$model->setState('list.limit', 10000);
			$model->setState('filter.state', [1]);
			$model->setState('list.start-date', 0);
			$model->setState('filter.expand', true);

			if ($model->getTotal() > $limit) {
				throw new \Exception('Too many series events!', 1);
			}

			$series = $model->getItems();
			foreach ($series as $e) {
				if (!self::openForBooking($e) || key_exists($e->id, $events)) {
					continue;
				}
				$events[$e->id] = $e;
			}
		}

		return $events;
	}

	public static function paymentRequired($event)
	{
		if (empty($event)) {
			return false;
		}

		return $event->price != '0.00' && !empty($event->price) && !empty($event->price->value);
	}

	public static function openForBooking($event)
	{
		if (!$event || $event->state == 3 || \DPCalendarHelper::isFree()) {
			return false;
		}

		if ($event->capacity !== null && $event->capacity_used >= $event->capacity) {
			return false;
		}

		$now                = \DPCalendarHelper::getDate();
		$regstrationEndDate = self::getRegistrationEndDate($event);

		if ($regstrationEndDate->format('U') < $now->format('U')) {
			return false;
		}

		$calendar = \DPCalendarHelper::getCalendar($event->catid);
		if (!$calendar) {
			return false;
		}

		return $calendar->canBook;
	}

	/**
	 * Return the closing end date for the event.
	 *
	 * @param \stdClass $event
	 *
	 * @return \Joomla\CMS\Date\Date
	 */
	public static function getRegistrationEndDate($event)
	{
		// When no closing date, use the start date
		if (empty($event->booking_closing_date)) {
			return \DPCalendarHelper::getDate($event->start_date);
		}

		// Check if it is a relative date
		if (strpos($event->booking_closing_date, '-') === 0 || strpos($event->booking_closing_date, '+') === 0) {
			$date = \DPCalendarHelper::getDate($event->start_date);
			$date->modify($event->booking_closing_date);

			return $date;
		}

		// Absolute date
		return \DPCalendarHelper::getDate($event->booking_closing_date);
	}

	/**
	 * Returns payment information for the given booking from the plugin the
	 * payment is made to.
	 *
	 * @param stdClass $booking
	 * @param Registry $params
	 *
	 * @return string
	 */
	public static function getPaymentStatementFromPlugin($booking, $params = null, $language = null)
	{
		\JPluginHelper::importPlugin('dpcalendarpay');

		$plugin    = substr($booking->processor, 0, strpos($booking->processor, '-'));
		$providers = \JFactory::getApplication()->triggerEvent('onDPPaymentProviders', [$plugin]);

		$provider = null;
		foreach ($providers as $pluginProviders) {
			foreach ($pluginProviders as $p) {
				if ($p->id == $booking->processor && !empty($p->payment_statement)) {
					$provider = $p;
					break;
				}
			}
		}

		if ($provider === null) {
			return '';
		}

		if (!$params) {
			$params = \JComponentHelper::getParams('com_dpcalendar');
		}

		$vars                               = (array)$booking;
		$vars['currency']                   = \DPCalendarHelper::getComponentParameter('currency', 'USD');
		$vars['currencySymbol']             = \DPCalendarHelper::getComponentParameter('currency_symbol', '$');
		$vars['currencySeparator']          = \DPCalendarHelper::getComponentParameter('currency_separator', '.');
		$vars['currencyThousandsSeparator'] = \DPCalendarHelper::getComponentParameter('currency_thousands_separator', "'");
		$vars['price_formatted']            = \DPCalendarHelper::renderPrice(
			$vars['price'],
			$vars['currencySymbol'],
			$vars['currencySeparator'],
			$vars['currencyThousandsSeparator']
		);

		if (!empty($booking->jcfields)) {
			foreach ($booking->jcfields as $field) {
				$vars['field-' . $field->name] = $field;
			}
		}

		if ($language == null) {
			$language = Factory::getLanguage();
		}

		$text = trim(strip_tags($provider->payment_statement));
		$text = $language->hasKey($text) ? $language->_($text) : $provider->payment_statement;

		return \DPCalendarHelper::renderEvents([], $text, $params, $vars);
	}

	/**
	 * Returns the discounted price if there are discounts to apply.
	 * If the early bird index is set, only the early bird with that index is
	 * used.
	 * If the user group index is set, only the user group discount with that
	 * index is
	 * used.
	 *
	 * @param decimal  $price
	 * @param stdclass $event
	 * @param integer  $earlyBirdIndex
	 * @param integer  $userGroupIndex
	 *
	 * @return number
	 */
	public static function getPriceWithDiscount($price, $event, $earlyBirdIndex = -1, $userGroupIndex = -1)
	{
		if (!$price) {
			return 0;
		}
		$newPrice = $price;

		$now = \DPCalendarHelper::getDate();

		if (is_object($event->earlybird) && isset($event->earlybird->value) && is_array($event->earlybird->value)) {
			foreach ($event->earlybird->value as $index => $value) {
				if (!$value || $earlyBirdIndex == -2 || ($earlyBirdIndex >= 0 && $earlyBirdIndex != $index)) {
					continue;
				}
				$limit = $event->earlybird->date[$index];
				$date  = \DPCalendarHelper::getDate($event->start_date);
				if (strpos($limit, '-') === 0 || strpos($limit, '+') === 0) {
					// Relative date
					$date->modify(str_replace('+', '-', $limit));
				} else {
					// Absolute date
					$date = \DPCalendarHelper::getDate($limit);
					if ($date->format('H:i') == '00:00') {
						$date->setTime(23, 59, 59);
					}
				}
				if ($date->format('U') < $now->format('U')) {
					continue;
				}

				if ($event->earlybird->type[$index] == 'value') {
					$newPrice = $newPrice - $value;
				} else {
					$newPrice = $newPrice - (($newPrice / 100) * $value);
				}

				if ($newPrice < 0) {
					$newPrice = 0;
				}

				break;
			}
		}
		$userGroups = \JAccess::getGroupsByUser(\JFactory::getUser()->id);
		if (is_object($event->user_discount) && isset($event->user_discount->value) && is_array($event->user_discount->value)) {
			foreach ($event->user_discount->value as $index => $value) {
				if (!$value || $userGroupIndex == -2 || ($userGroupIndex >= 0 && $userGroupIndex != $index)) {
					continue;
				}
				$groups = $event->user_discount->discount_groups[$index];
				if (!array_intersect($userGroups, $groups)) {
					continue;
				}

				if ($event->user_discount->type[$index] == 'value') {
					$newPrice = $newPrice - $value;
				} else {
					$newPrice = $newPrice - (($newPrice / 100) * $value);
				}

				if ($newPrice < 0) {
					$newPrice = 0;
				}

				break;
			}
		}

		return $newPrice;
	}

	public static function getStatusLabel($booking)
	{
		$status = 'COM_DPCALENDAR_BOOKING_FIELD_STATE_UNPUBLISHED';
		switch ($booking->state) {
			case 0:
				$status = 'COM_DPCALENDAR_BOOKING_FIELD_STATE_UNPUBLISHED';
				break;
			case 1:
				$status = 'COM_DPCALENDAR_BOOKING_FIELD_STATE_PUBLISHED';
				break;
			case 2:
				$status = 'COM_DPCALENDAR_BOOKING_FIELD_STATE_TICKET_REVIEW';
				break;
			case 3:
				$status = 'COM_DPCALENDAR_BOOKING_FIELD_STATE_CONFIRMATION';
				break;
			case 4:
				$status = 'COM_DPCALENDAR_BOOKING_FIELD_STATE_HOLD';
				break;
			case 5:
				$status = 'COM_DPCALENDAR_BOOKING_FIELD_STATE_INVITED';
				break;
			case 6:
				$status = 'COM_DPCALENDAR_BOOKING_FIELD_STATE_CANCELED';
				break;
			case 7:
				$status = 'COM_DPCALENDAR_BOOKING_FIELD_STATE_REFUNDED';
				break;
			case -2:
				$status = 'JTRASHED';
				break;
		}

		return \JText::_($status);
	}
}
