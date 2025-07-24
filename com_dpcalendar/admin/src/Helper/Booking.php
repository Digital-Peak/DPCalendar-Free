<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2014 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Component\DPCalendar\Administrator\Helper;

\defined('_JEXEC') or die();

use chillerlan\QRCode\QRCode;
use DigitalPeak\Component\DPCalendar\Administrator\Calendar\CalendarInterface;
use DigitalPeak\Component\DPCalendar\Administrator\Translator\Translator;
use DigitalPeak\Component\DPCalendar\Site\Helper\RouteHelper;
use Dompdf\Canvas;
use Dompdf\Dompdf;
use Dompdf\FontMetrics;
use Dompdf\Options;
use Joomla\CMS\Access\Access;
use Joomla\CMS\Date\Date;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Language;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Registry\Registry;

class Booking
{
	/**
	 * Creates a PDF for the given booking and tickets.
	 * If to file is set, then the PDF will be written to a file and the file
	 * name is returned. Otherwise it will be offered as download.
	 */
	public static function createReceipt(\stdClass $booking, array $tickets, Registry $params, bool $toFile = false, ?Language $language = null): ?string
	{
		try {
			if ($language == null) {
				$language = Factory::getApplication()->getLanguage();
			}
			$language->load('com_dpcalendar', JPATH_ADMINISTRATOR . '/components/com_dpcalendar');

			$details = DPCalendarHelper::renderLayout(
				'booking.receipt',
				[
					'booking'    => $booking,
					'tickets'    => $tickets,
					'translator' => new Translator($language),
					'dateHelper' => new DateHelper(),
					'params'     => $params
				]
			);

			return self::createPDF($details, $booking->uid, $toFile);
		} catch (\Exception $exception) {
			Factory::getApplication()->enqueueMessage($exception->getMessage(), 'warning');

			return null;
		}
	}

	/**
	 * Loads the invoice from the given booking.
	 */
	public static function getInvoice(\stdClass $booking, bool $toFile = false): string
	{
		// Load the payment plugins
		PluginHelper::importPlugin('dpcalendarpay');
		$invoice = Factory::getApplication()->triggerEvent('onDPCalendarLoadInvoice', ['booking' => $booking]);

		// Set the invoice to the first element when available
		if (!$invoice || !$invoice[0]) {
			return '';
		}
		$invoice = $invoice[0];

		// Write the file
		if ($toFile) {
			file_put_contents(JPATH_ROOT . '/tmp/' . $invoice['name'], $invoice['content']);

			return JPATH_ROOT . '/tmp/' . $invoice['name'];
		}

		// Download the invoice
		header('Content-Type: ' . ($invoice['mime'] ?? 'application/pdf'));
		header('Content-disposition: attachment; filename="' . $invoice['name'] . '"');

		if (!empty($invoice['size'])) {
			header('Content-Length: ' . $invoice['size']);
		}

		echo $invoice['content'];

		return $invoice['name'];
	}

	/**
	 * Creates a PDF for the given ticket.
	 * If to file is set, then the PDF will be written to a file and the file
	 * name is returned. Otherwise it will be offered as download.
	 */
	public static function createTicket(\stdClass $ticket, Registry $params, bool $toFile = false): ?string
	{
		try {
			Factory::getApplication()->getLanguage()->load('com_dpcalendar', JPATH_ADMINISTRATOR . '/components/com_dpcalendar');

			DPCalendarHelper::increaseMemoryLimit(130 * 1024 * 1024);

			$model = Factory::getApplication()->bootComponent('dpcalendar')->getMVCFactory()->createModel('Event', 'Site', ['ignore_request' => true]);
			$event = $model->getItem($ticket->event_id);

			$qrcode = '';
			if ($params->get('ticket_show_barcode', 1)) {
				$qrcode = (new QRCode())->render(RouteHelper::getTicketCheckinRoute($ticket, true));
			}

			$details = DPCalendarHelper::renderLayout(
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
		} catch (\Exception $exception) {
			Factory::getApplication()->enqueueMessage($exception->getMessage(), 'warning');

			return null;
		}
	}
	/**
	 * Creates a PDF for the given ticket.
	 * If to file is set, then the PDF will be written to a file and the file
	 * name is returned. Otherwise it will be offered as download.
	 */
	public static function createCertificate(\stdClass $ticket, Registry $params, bool $toFile = false, ?\stdClass $booking = null): ?string
	{
		$app = Factory::getApplication();

		try {
			$app->getLanguage()->load('com_dpcalendar', JPATH_ADMINISTRATOR . '/components/com_dpcalendar');

			$model = $app->bootComponent('dpcalendar')->getMVCFactory()->createModel('Event', 'Site', ['ignore_request' => true]);
			$event = $model->getItem($ticket->event_id);
			if (!$event) {
				return null;
			}

			$event->text = $event->description;
			$app->triggerEvent('onContentPrepare', ['com_dpcalendar.event', &$event, &$params, 0]);
			$event->description = $event->text;

			if (!$booking instanceof \stdClass) {
				$model   = $app->bootComponent('dpcalendar')->getMVCFactory()->createModel('Booking', 'Administrator', ['ignore_request' => true]);
				$booking = $model->getItem($ticket->booking_id);
			}

			if (!$booking) {
				return null;
			}

			$booking->text = '';
			$app->triggerEvent('onContentPrepare', ['com_dpcalendar.booking', &$booking, &$params, 0]);

			if (!isset($ticket->jcfields)) {
				$ticket->text = '';
				$app->triggerEvent('onContentPrepare', ['com_dpcalendar.ticket', &$ticket, &$params, 0]);
			}

			$details = DPCalendarHelper::renderLayout(
				'ticket.certificate',
				[
					'booking'      => $booking,
					'ticket'       => $ticket,
					'event'        => $event,
					'translator'   => new Translator(),
					'dateHelper'   => new DateHelper(),
					'layoutHelper' => $app->bootComponent('dpcalendar')->getMVCFactory()->createModel('Layout', 'Administrator'),
					'params'       => $params
				]
			);

			return self::createPDF($details, $ticket->uid, $toFile);
		} catch (\Exception $exception) {
			$app->enqueueMessage($exception->getMessage(), 'warning');

			return null;
		}
	}

	/**
	 * Returns the series events. If there are more than the given limit
	 * an exception is thrown.
	 */
	public static function getSeriesEvents(\stdClass $event, int $limit = 40): array
	{
		$events = [$event->id => $event];
		if ($event->original_id != '0') {
			$model = Factory::getApplication()->bootComponent('dpcalendar')->getMVCFactory()->createModel('Events', 'Site', ['ignore_request' => true]);

			$model->getState();
			$model->setState('filter.children', $event->original_id == -1 ? $event->id : $event->original_id);
			$model->setState('list.limit', 10000);
			$model->setState('filter.state', [1, $event->state]);
			$model->setState('list.start-date', 0);
			$model->setState('filter.expand', true);

			if ($model->getTotal() > $limit) {
				throw new \Exception('Too many series events!', 1);
			}

			$series = $model->getItems();
			foreach ($series as $e) {
				if (!self::openForBooking($e) || \array_key_exists($e->id, $events)) {
					continue;
				}
				$events[$e->id] = $e;
			}
		}

		return $events;
	}

	public static function paymentRequired(\stdClass $event): bool
	{
		return !empty($event->prices) || !empty($event->booking_options);
	}

	public static function openForCancel(\stdClass $booking, array $states = [1, 4, 8]): bool
	{
		// @phpstan-ignore-next-line
		if (Factory::getUser()->authorise('dpcalendar.admin.book', 'com_dpcalendar')) {
			return true;
		}

		if (!\in_array($booking->state, $states)) {
			return false;
		}

		foreach ($booking->tickets as $ticket) {
			$now        = DPCalendarHelper::getDate();
			$cancelDate = DPCalendarHelper::getDate(
				empty($ticket->event_booking_cancel_closing_date) ? $ticket->start_date : $ticket->event_booking_cancel_closing_date
			);

			// Check if it is a relative date
			if (str_starts_with($ticket->event_booking_cancel_closing_date ?? '', '-')
				|| str_starts_with($ticket->event_booking_cancel_closing_date ?? '', '+')) {
				$cancelDate = DPCalendarHelper::getDate($ticket->start_date);
				$cancelDate->modify($ticket->event_booking_cancel_closing_date);
			}

			if ($cancelDate->format('U') > $now->format('U')) {
				return true;
			}
		}

		return false;
	}

	public static function openForBooking(\stdClass $event): bool
	{
		if ($event->state == 3 || DPCalendarHelper::isFree()) {
			return false;
		}

		if ($event->capacity !== null && $event->capacity_used >= $event->capacity && !$event->booking_waiting_list) {
			return false;
		}

		$now                 = DPCalendarHelper::getDate();
		$registrationEndDate = self::getRegistrationEndDate($event);
		if ($registrationEndDate->format('U') < $now->format('U')) {
			return false;
		}

		$registrationEndDate = self::getRegistrationStartDate($event);
		if ($registrationEndDate->format('U') > $now->format('U')) {
			return false;
		}

		$calendar = Factory::getApplication()->bootComponent('dpcalendar')->getMVCFactory()->createModel('Calendar', 'Administrator')->getCalendar($event->catid);
		if (!$calendar instanceof CalendarInterface) {
			return false;
		}

		return $calendar->canBook();
	}

	/**
	 * Return the opening start date for the event registration.
	 */
	public static function getRegistrationStartDate(\stdClass $event): Date
	{
		// When no opening date, use the event published date
		if (empty($event->booking_opening_date)) {
			return DPCalendarHelper::getDate($event->created);
		}

		// Check if it is a relative date
		if (str_starts_with((string)$event->booking_opening_date, '-') || str_starts_with((string)$event->booking_opening_date, '+')) {
			$date = DPCalendarHelper::getDate($event->start_date);
			$date->modify($event->booking_opening_date);

			return $date;
		}

		// Absolute date
		return DPCalendarHelper::getDate($event->booking_opening_date, \strlen($event->booking_opening_date) === 10);
	}

	/**
	 * Return the closing end date for the event.
	 */
	public static function getRegistrationEndDate(\stdClass $event): Date
	{
		// When no closing date, use the start date
		if (empty($event->booking_closing_date)) {
			return DPCalendarHelper::getDate($event->start_date);
		}

		// Check if it is a relative date
		if (str_starts_with((string)$event->booking_closing_date, '-') || str_starts_with((string)$event->booking_closing_date, '+')) {
			$date = DPCalendarHelper::getDate($event->start_date);
			$date->modify($event->booking_closing_date);

			return $date;
		}

		// Absolute date
		return DPCalendarHelper::getDate($event->booking_closing_date, \strlen($event->booking_closing_date) === 10);
	}

	/**
	 * Returns the discounted price if there are discounts to apply for the defined area (ticket, booking, option).
	 * If the early bird key is set, only the early bird with that key is used.
	 * If the user group key is set, only the user group discount with that key is used.
	 */
	public static function getPriceWithDiscount(float $price, \stdClass $event, string $earlyBirdKey = '', string $userGroupKey = '', string $area = 'ticket'): float
	{
		if ($price === 0.0) {
			return 0;
		}

		$newPrice = $price;

		$now = DPCalendarHelper::getDate();

		if ($event->earlybird_discount instanceof \stdClass && $earlyBirdKey !== '-1') {
			foreach ((array)$event->earlybird_discount as $key => $discount) {
				$discount->area ??= 'ticket';
				if (!$discount->value || $discount->area !== $area || ($earlyBirdKey !== '' && $earlyBirdKey !== $key)) {
					continue;
				}

				$limit = $discount->date;
				$date  = DPCalendarHelper::getDate($event->start_date);
				if (str_starts_with((string)$limit, '-') || str_starts_with((string)$limit, '+')) {
					// Relative date
					$date->modify(str_replace('+', '-', (string)$limit));
				} else {
					// Absolute date
					$date = DPCalendarHelper::getDate($limit);
					if ($date->format('H:i') === '00:00') {
						$date->setTime(23, 59, 59);
					}
				}

				if ($date->format('U') < $now->format('U')) {
					continue;
				}

				$newPrice = $discount->type == 'value' ? $newPrice - $discount->value : $newPrice - (($newPrice / 100) * $discount->value);

				if ($newPrice < 0) {
					$newPrice = 0;
				}

				break;
			}
		}

		// @phpstan-ignore-next-line
		$userGroups = Access::getGroupsByUser(Factory::getUser()->id);
		if ($event->user_discount instanceof \stdClass && $userGroupKey !== '-1') {
			foreach ((array)$event->user_discount as $key => $discount) {
				$discount->area ??= 'ticket';
				if (!$discount->value || $discount->area !== $area || ($userGroupKey !== '' && $userGroupKey !== $key)) {
					continue;
				}

				$groups = $discount->groups ?? [];
				if (!$groups || array_intersect($userGroups, $groups) === []) {
					continue;
				}

				$newPrice = $discount->type == 'value' ? $newPrice - $discount->value : $newPrice - (($newPrice / 100) * $discount->value);

				if ($newPrice < 0) {
					$newPrice = 0;
				}

				break;
			}
		}

		return $newPrice;
	}

	public static function getStatusLabel(\stdClass $booking): string
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

	public static function createPDF(string $details, string $name, bool $toFile): string
	{
		if (!file_exists(JPATH_ADMINISTRATOR . '/components/com_dpcalendar/vendor/dompdf/dompdf/lib/fonts/installed-fonts.dist.json')) {
			copy(
				JPATH_ADMINISTRATOR . '/components/com_dpcalendar/config/Dompdf/installed-fonts.dist.json',
				JPATH_ADMINISTRATOR . '/components/com_dpcalendar/vendor/dompdf/dompdf/lib/fonts/installed-fonts.dist.json'
			);
		}

		$options = new Options();
		$options->set('defaultFont', 'DejaVu Sans');
		$options->set('isRemoteEnabled', true);
		$options->set('isPhpEnabled', true);
		$options->setChroot(array_merge([JPATH_ROOT], $options->getChroot()));
		$options->setTempDir(Factory::getApplication()->get('tmp_path', JPATH_ROOT . '/tmp'));

		$dompdf = new Dompdf($options);
		$dompdf->loadHtml($details);
		$dompdf->setBasePath(JPATH_ROOT);
		$dompdf->render();

		if (!str_contains($details, '<footer')) {
			$dompdf->getCanvas()->page_script(static function (int $pageNumber, int $pageCount, Canvas $canvas, FontMetrics $fontMetrics): void {
				$format = DPCalendarHelper::getComponentParameter('event_date_format', 'd.m.Y')
					. ' ' . DPCalendarHelper::getComponentParameter('event_time_format', 'H:i');
				$font = $fontMetrics->getFont('DejaVu Sans') ?: '';
				$canvas->text(50, $canvas->get_height() - 50, DPCalendarHelper::getDate()->format($format, true), $font, 8);
				$canvas->text($canvas->get_width() - 70, $canvas->get_height() - 50, $pageNumber . '/' . $pageCount, $font, 8);
				$canvas->line(50, $canvas->get_height() - 60, $canvas->get_width() - 50, $canvas->get_height() - 60, [0, 0, 0], 1);
			});
		}

		$fileName = $name . '.pdf';
		if ($toFile) {
			$fileName = $options->getTempDir() . '/' . $fileName;
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
