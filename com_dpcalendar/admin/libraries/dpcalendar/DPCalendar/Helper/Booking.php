<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2018 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */
namespace DPCalendar\Helper;

use DPCalendar\TCPDF\DPCalendar;
use Joomla\Registry\Registry;
use CCL\Content\Element\Basic\Container;
use DPCalendar\CCL\Visitor\InlineStyleVisitor;

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
	 *
	 * @return string
	 */
	public static function createInvoice($booking, $tickets, $params, $toFile = false)
	{
		try {
			$root = new Container('dp-booking');
			\DPCalendarHelper::renderLayout(
				'booking.invoice',
				array(
					'booking' => $booking,
					'tickets' => $tickets,
					'params'  => $params,
					'root'    => $root
				)
			);
			$root->accept(new InlineStyleVisitor());

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

			// set default monospaced font
			$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

			// set margins
			$pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
			$pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
			$pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

			// Font sizes
			$pdf->setHeaderFont(array($pdf->getFontFamily(), '', 9));
			$pdf->setFooterFont(array($pdf->getFontFamily(), '', 9));

			// Adding the content
			$pdf->AddPage();
			$pdf->writeHTML(\DPCalendarHelper::renderElement($root), true, false, true, false, '');

			$fileName = $booking->uid . '.pdf';
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
			\DPCalendarHelper::increaseMemoryLimit(130 * 1024 * 1024);

			\JModelLegacy::addIncludePath(JPATH_SITE . '/components/com_dpcalendar/models');
			$model = \JModelLegacy::getInstance('Event', 'DPCalendarModel', array('ignore_request' => true));
			$event = $model->getItem($ticket->event_id);

			$root = new Container('dp-ticket');
			\DPCalendarHelper::renderLayout(
				'ticket.details',
				array(
					'ticket' => $ticket,
					'event'  => $event,
					'params' => $params,
					'root'   => $root
				)
			);
			$root->accept(new InlineStyleVisitor());

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

			// set default monospaced font
			$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

			// set margins
			$pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
			$pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
			$pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

			// Font sizes
			$pdf->setHeaderFont(array($pdf->getFontFamily(), '', 9));
			$pdf->setFooterFont(array($pdf->getFontFamily(), '', 9));

			// Adding the content
			$pdf->AddPage();
			$pdf->writeHTML(\DPCalendarHelper::renderElement($root), true, false, true, false, '');

			if ($params->get('ticket_show_barcode', 1)) {
				$style = array(
					'border'        => 2,
					'position'      => 'C',
					'vpadding'      => 'auto',
					'hpadding'      => 'auto',
					'fgcolor'       => array(0, 0, 0),
					'bgcolor'       => false,
					'module_width'  => 1,
					'module_height' => 1
				);
				$pdf->write2DBarcode(\DPCalendarHelperRoute::getTicketRoute($ticket, true), 'QRCODE,L', 20, 200, 50, 50, $style, 'N');
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

	public static function getSeriesEvents($event)
	{
		if (!$event) {
			return array();
		}

		$events = array(
			$event->id => $event
		);
		if ($event->original_id != '0') {
			$model = \JModelLegacy::getInstance('Events', 'DPCalendarModel', array('ignore_request' => true));
			if (!$model) {
				$model = \JModelLegacy::getInstance('AdminEvents', 'DPCalendarModel', array('ignore_request' => true));
			}
			$model->getState();
			$model->setState('filter.children', $event->original_id == -1 ? $event->id : $event->original_id);
			$model->setState('list.limit', 10000);
			$model->setState('filter.expand', true);

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
		if (!$event || \DPCalendarHelper::isFree()) {
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

		// Check if it is a realative date
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
	public static function getPaymentStatementFromPlugin($booking, $params = null)
	{
		\JPluginHelper::importPlugin('dpcalendarpay');
		$statement = \JFactory::getApplication()->triggerEvent('onDPPaymentStatement', array($booking));

		$buffer = '';
		if ($statement) {
			if (!$params) {
				$params = \JComponentHelper::getParams('com_dpcalendar');
			}

			$vars                   = (array)$booking;
			$vars['currency']       = \DPCalendarHelper::getComponentParameter('currency', 'USD');
			$vars['currencySymbol'] = \DPCalendarHelper::getComponentParameter('currency_symbol', '$');
			foreach ($statement as $b) {
				if ($b->status && $booking->type = $b->type) {
					$buffer .= \DPCalendarHelper::renderEvents(array(), $b->statement, $params, $vars);
				}
			}
		}

		return $buffer;
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
			return $price;
		}
		$newPrice = $price;

		$now = \DPCalendarHelper::getDate();

		if (is_object($event->earlybird) && isset($event->earlybird->value) && is_array($event->earlybird->value)) {
			foreach ($event->earlybird->value as $index => $value) {
				if ($earlyBirdIndex == -2 || ($earlyBirdIndex >= 0 && $earlyBirdIndex != $index)) {
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
				if ($userGroupIndex == -2 || ($userGroupIndex >= 0 && $userGroupIndex != $index)) {
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
				$status = 'JARCHIVED';
				break;
			case 3:
				$status = 'COM_DPCALENDAR_BOOKING_FIELD_STATE_NEED_PAYMENT';
				break;
			case 4:
				$status = 'COM_DPCALENDAR_BOOKING_FIELD_STATE_HOLD';
				break;
			case 5:
				$status = 'COM_DPCALENDAR_BOOKING_FIELD_STATE_INVITED';
				break;
			case -2:
				$status = 'JTRASHED';
				break;
		}

		return \JText::_($status);
	}
}
