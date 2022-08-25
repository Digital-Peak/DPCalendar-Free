<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2014 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DPCalendar\Helper;

defined('_JEXEC') or die();

use chillerlan\QRCode\QRCode;
use Dompdf\Dompdf;
use Dompdf\Options;
use DPCalendar\Translator\Translator;
use DPCalendarHelperRoute;
use Exception;
use Joomla\CMS\Access\Access;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Language;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Table\Table;
use Joomla\Registry\Registry;

\JLoader::import('joomla.application.component.helper');
Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_dpcalendar/tables');

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
		// Load the payment plugins
		PluginHelper::importPlugin('dpcalendarpay');
		$invoice = Factory::getApplication()->triggerEvent('onDPCalendarLoadInvoice', ['booking' => $booking]);

		// Set the invoice to the first element when available
		if ($invoice) {
			$invoice = $invoice[0];
		}

		// Write the file
		if ($invoice && $toFile) {
			file_put_contents(JPATH_ROOT . '/tmp/' . $invoice['name'], $invoice['content']);

			return JPATH_ROOT . '/tmp/' . $invoice['name'];
		}

		// Download the invoice
		if ($invoice && !$toFile) {
			header('Content-Type: ' . ($invoice['mime'] ?? 'application/pdf'));
			header('Content-disposition: attachment; filename="' . $invoice['name'] . '"');

			if (!empty($invoice['size'])) {
				header('Content-Length: ' . $invoice['size']);
			}

			echo $invoice['content'];

			return $invoice['name'];
		}

		try {
			$details = $booking->invoice;

			if (!$details) {
				if ($language == null) {
					$language = Factory::getLanguage();
				}
				$language->load('com_dpcalendar', JPATH_ADMINISTRATOR . '/components/com_dpcalendar');
				$details = DPCalendarHelper::renderLayout(
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

			return self::createPDF($details, $booking->uid, $toFile);
		} catch (Exception $e) {
			Factory::getApplication()->enqueueMessage($e->getMessage(), 'warning');

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
			Factory::getLanguage()->load('com_dpcalendar', JPATH_ADMINISTRATOR . '/components/com_dpcalendar');

			DPCalendarHelper::increaseMemoryLimit(130 * 1024 * 1024);

			BaseDatabaseModel::addIncludePath(JPATH_SITE . '/components/com_dpcalendar/models');
			$model = BaseDatabaseModel::getInstance('Event', 'DPCalendarModel', ['ignore_request' => true]);
			$event = $model->getItem($ticket->event_id);

			$qrcode = '';
			if ($params->get('ticket_show_barcode', 1)) {
				$qrcode = (new QRCode())->render(DPCalendarHelperRoute::getTicketCheckinRoute($ticket, true));
			}

			$details = \DPCalendarHelper::renderLayout(
				'ticket.details',
				[
					'ticket'     => $ticket,
					'event'      => $event,
					'qrcode'     => $qrcode,
					'translator' => new Translator(),
					'dateHelper' => new DateHelper(),
					'params'     => $params
				]
			);

			return self::createPDF($details, $ticket->uid, $toFile);
		} catch (Exception $e) {
			Factory::getApplication()->enqueueMessage($e->getMessage(), 'warning');

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
	public static function createCertificate($ticket, $params, $toFile = false)
	{
		try {
			Factory::getLanguage()->load('com_dpcalendar', JPATH_ADMINISTRATOR . '/components/com_dpcalendar');

			BaseDatabaseModel::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_dpcalendar/models');
			BaseDatabaseModel::addIncludePath(JPATH_SITE . '/components/com_dpcalendar/models');

			$model       = BaseDatabaseModel::getInstance('Event', 'DPCalendarModel', ['ignore_request' => true]);
			$event       = $model->getItem($ticket->event_id);
			$event->text = $event->description;
			Factory::getApplication()->triggerEvent('onContentPrepare', ['com_dpcalendar.event', &$event, &$params, 0]);
			$event->description = $event->text;

			$model         = BaseDatabaseModel::getInstance('Booking', 'DPCalendarModel', ['ignore_request' => true]);
			$booking       = $model->getItem($ticket->booking_id);
			$booking->text = '';
			Factory::getApplication()->triggerEvent('onContentPrepare', ['com_dpcalendar.booking', &$booking, &$params, 0]);

			if (!isset($ticket->jcfields)) {
				$ticket->text = '';
				Factory::getApplication()->triggerEvent('onContentPrepare', ['com_dpcalendar.ticket', &$ticket, &$params, 0]);
			}

			$details = \DPCalendarHelper::renderLayout(
				'ticket.certificate',
				[
					'booking'      => $booking,
					'ticket'       => $ticket,
					'event'        => $event,
					'translator'   => new Translator(),
					'dateHelper'   => new DateHelper(),
					'layoutHelper' => new LayoutHelper(),
					'params'       => $params
				]
			);

			return self::createPDF($details, $ticket->uid, $toFile);
		} catch (Exception $e) {
			Factory::getApplication()->enqueueMessage($e->getMessage(), 'warning');

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
			$model = BaseDatabaseModel::getInstance('Events', 'DPCalendarModel', ['ignore_request' => true]);
			if (!$model) {
				$model = BaseDatabaseModel::getInstance('AdminEvents', 'DPCalendarModel', ['ignore_request' => true]);
			}

			$model->getState();
			$model->setState('filter.children', $event->original_id == -1 ? $event->id : $event->original_id);
			$model->setState('list.limit', 10000);
			$model->setState('filter.state', [1]);
			$model->setState('list.start-date', 0);
			$model->setState('filter.expand', true);

			if ($model->getTotal() > $limit) {
				throw new Exception('Too many series events!', 1);
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
		if (!$event || $event->state == 3 || DPCalendarHelper::isFree()) {
			return false;
		}

		if ($event->capacity !== null && $event->capacity_used >= $event->capacity && !$event->booking_waiting_list) {
			return false;
		}

		$now                = DPCalendarHelper::getDate();
		$regstrationEndDate = self::getRegistrationEndDate($event);
		if ($regstrationEndDate->format('U') < $now->format('U')) {
			return false;
		}

		$regstrationStartDate = self::getRegistrationStartDate($event);
		if ($regstrationStartDate->format('U') > $now->format('U')) {
			return false;
		}

		$calendar = DPCalendarHelper::getCalendar($event->catid);
		if (!$calendar) {
			return false;
		}

		return $calendar->canBook;
	}

	/**
	 * Return the opening start date for the event registration.
	 *
	 * @param \stdClass $event
	 *
	 * @return \Joomla\CMS\Date\Date
	 */
	public static function getRegistrationStartDate($event)
	{
		// When no opening date, use the event published date
		if (empty($event->booking_opening_date)) {
			return DPCalendarHelper::getDate($event->created);
		}

		// Check if it is a relative date
		if (strpos($event->booking_opening_date, '-') === 0 || strpos($event->booking_opening_date, '+') === 0) {
			$date = DPCalendarHelper::getDate($event->start_date);
			$date->modify($event->booking_opening_date);

			return $date;
		}

		// Absolute date
		return DPCalendarHelper::getDate($event->booking_opening_date);
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
			return DPCalendarHelper::getDate($event->start_date);
		}

		// Check if it is a relative date
		if (strpos($event->booking_closing_date, '-') === 0 || strpos($event->booking_closing_date, '+') === 0) {
			$date = DPCalendarHelper::getDate($event->start_date);
			$date->modify($event->booking_closing_date);

			return $date;
		}

		// Absolute date
		return DPCalendarHelper::getDate($event->booking_closing_date);
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
		PluginHelper::importPlugin('dpcalendarpay');

		$plugin    = substr($booking->processor, 0, strpos($booking->processor, '-'));
		$providers = Factory::getApplication()->triggerEvent('onDPPaymentProviders', [$plugin]);

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
			$params = ComponentHelper::getParams('com_dpcalendar');
		}

		$vars                               = (array)$booking;
		$vars['currency']                   = DPCalendarHelper::getComponentParameter('currency', 'USD');
		$vars['currencySymbol']             = DPCalendarHelper::getComponentParameter('currency_symbol', '$');
		$vars['currencySeparator']          = DPCalendarHelper::getComponentParameter('currency_separator', '.');
		$vars['currencyThousandsSeparator'] = DPCalendarHelper::getComponentParameter('currency_thousands_separator', "'");
		$vars['price_formatted']            = DPCalendarHelper::renderPrice(
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

		return DPCalendarHelper::renderEvents([], $text, $params, $vars);
	}

	/**
	 * Returns the discounted price if there are discounts to apply.
	 * If the early bird index is set, only the early bird with that index is
	 * used.
	 * If the user group index is set, only the user group discount with that
	 * index is
	 * used.
	 *
	 * @param float     $price
	 * @param \stdclass $event
	 * @param integer   $earlyBirdIndex
	 * @param integer   $userGroupIndex
	 *
	 * @return float
	 */
	public static function getPriceWithDiscount($price, $event, $earlyBirdIndex = -1, $userGroupIndex = -1)
	{
		if (!$price) {
			return 0;
		}
		$newPrice = $price;

		$now = DPCalendarHelper::getDate();

		if (is_object($event->earlybird) && isset($event->earlybird->value) && is_array($event->earlybird->value)) {
			foreach ($event->earlybird->value as $index => $value) {
				if (!$value || $earlyBirdIndex == -2 || ($earlyBirdIndex >= 0 && $earlyBirdIndex != $index)) {
					continue;
				}
				$limit = $event->earlybird->date[$index];
				$date  = DPCalendarHelper::getDate($event->start_date);
				if (strpos($limit, '-') === 0 || strpos($limit, '+') === 0) {
					// Relative date
					$date->modify(str_replace('+', '-', $limit));
				} else {
					// Absolute date
					$date = DPCalendarHelper::getDate($limit);
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
		$userGroups = Access::getGroupsByUser(Factory::getUser()->id);
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
				break;
			case 8:
				$status = 'COM_DPCALENDAR_BOOKING_FIELD_STATE_WAITING';
				break;
			case 9:
				$status = 'COM_DPCALENDAR_TICKET_FIELD_STATE_CHECKIN';
				break;
			case -2:
				$status = 'JTRASHED';
				break;
		}

		return Text::_($status);
	}

	private static function createPDF(string $details, string $name, bool $toFile)
	{
		if (!file_exists(JPATH_ADMINISTRATOR . '/components/com_dpcalendar/vendor/dompdf/dompdf/lib/fonts/installed-fonts.dist.json')) {
			copy(
				JPATH_ADMINISTRATOR . '/components/com_dpcalendar/libraries/dpcalendar/DPCalendar/Dompdf/installed-fonts.dist.json',
				JPATH_ADMINISTRATOR . '/components/com_dpcalendar/vendor/dompdf/dompdf/lib/fonts/installed-fonts.dist.json'
			);
		}

		$options = new Options();
		$options->set('defaultFont', 'DejaVu Sans');
		$options->set('isRemoteEnabled', true);
		$dompdf = new Dompdf($options);
		$dompdf->loadHtml($details);
		$dompdf->render();

		$fileName = $name . '.pdf';
		if ($toFile) {
			$fileName = JPATH_ROOT . '/tmp/' . $fileName;
			if (file_exists($fileName)) {
				unlink($fileName);
			}
		}

		if ($toFile) {
			file_put_contents($fileName, $dompdf->output());
		} else {
			$dompdf->stream($fileName);
		}

		return $fileName;
	}
}
