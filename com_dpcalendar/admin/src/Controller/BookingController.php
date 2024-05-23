<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2014 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Component\DPCalendar\Administrator\Controller;

defined('_JEXEC') or die();

use DigitalPeak\Component\DPCalendar\Administrator\Helper\Booking;
use DigitalPeak\Component\DPCalendar\Administrator\Helper\DateHelper;
use DigitalPeak\Component\DPCalendar\Administrator\Helper\DPCalendarHelper;
use DigitalPeak\Component\DPCalendar\Administrator\Translator\Translator;
use DigitalPeak\Component\DPCalendar\Site\Helper\RouteHelper;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Mail\Mail;
use Joomla\CMS\Mail\MailerFactoryAwareInterface;
use Joomla\CMS\Mail\MailerFactoryAwareTrait;
use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\User\CurrentUserInterface;
use Joomla\CMS\User\CurrentUserTrait;
use Joomla\CMS\User\UserFactoryAwareInterface;
use Joomla\CMS\User\UserFactoryAwareTrait;

class BookingController extends FormController implements UserFactoryAwareInterface, MailerFactoryAwareInterface, CurrentUserInterface
{
	use UserFactoryAwareTrait;
	use MailerFactoryAwareTrait;
	use CurrentUserTrait;

	protected $text_prefix = 'COM_DPCALENDAR_BOOKING';

	public function mail(): void
	{
		$data = (array)$this->getUserFactory()->loadUserById($this->input->getInt('id', 0));
		unset($data['password']);

		$model = $this->app->bootComponent('contact')->getMVCFactory()->createModel('Contact', 'Administrator', ['ignore_request' => true]);
		// @phpstan-ignore-next-line
		$contact = $model->getItem(['user_id' => $this->input->getInt('id', 0)]);
		if ($contact && $contact->id) {
			$data['country']   = $contact->country;
			$data['province']  = $contact->state;
			$data['city']      = $contact->suburb;
			$data['zip']       = $contact->postcode;
			$data['street']    = $contact->address;
			$data['telephone'] = $contact->telephone;
		}

		foreach ($this->getModel()->getProfileData($this->input->getInt('id', 0)) as $profileData) {
			$value = json_decode((string)$profileData[1]);
			switch ($profileData[0]) {
				case 'profile.address1':
				case 'profile.address2':
					if ($value && !empty($data['street'])) {
						$data['street'] = $value;
					}
					break;
				case 'profile.city':
					if ($value && !empty($data['city'])) {
						$data['city'] = $value;
					}
					break;
				case 'profile.postal_code':
					if ($value && !empty($data['zip'])) {
						$data['zip'] = $value;
					}
					break;
				case 'profile.region':
					if ($value && !empty($data['province'])) {
						$data['province'] = $value;
					}
					break;
				case 'profile.phone':
					if ($value && !empty($data['telephone'])) {
						$data['telephone'] = $value;
					}
					break;
			}
		}

		$coordinates = $this->app->bootComponent('dpcalendar')->getMVCFactory()->createModel('Geo', 'Administrator')->getLocation($this->app->bootComponent('dpcalendar')->getMVCFactory()->createModel('Geo', 'Administrator')->format([(object)$data]), false);
		if ($coordinates->latitude) {
			$data['latitude'] = $coordinates->latitude;
		}
		if ($coordinates->longitude) {
			$data['longitude'] = $coordinates->longitude;
		}

		DPCalendarHelper::sendMessage('', false, $data);
	}

	public function invoice(): void
	{
		$model = $this->getModel('Booking', 'Administrator', ['ignore_request' => false]);
		$model->getState();

		$booking = $model->getItem();

		if ($booking == null) {
			throw new \Exception(Text::_('JERROR_ALERTNOAUTHOR'), 403);
		}

		$fileName = Booking::getInvoice($booking);
		if ($fileName !== '' && $fileName !== '0') {
			$this->app->close();
		} else {
			$this->app->redirect(RouteHelper::getBookingRoute($booking));
		}
	}

	public function invoicesend(): void
	{
		$model   = $this->getModel('Booking', 'Administrator', ['ignore_request' => false]);
		$booking = $model->getItem();

		if ($booking == null) {
			throw new \Exception('JERROR_ALERTNOAUTHOR', 403);
		}

		$this->app->getLanguage()->load('com_dpcalendar', JPATH_ADMINISTRATOR . '/components/com_dpcalendar');

		$params = clone ComponentHelper::getParams('com_dpcalendar');
		$params->set('show_header', false);
		$params->set('pdf_header', '');
		$params->set('pdf_content_top', '');
		$params->set('pdf_content_bottom', '');

		$details = DPCalendarHelper::renderLayout(
			'booking.details',
			[
				'booking'    => $booking,
				'tickets'    => $booking->tickets,
				'translator' => new Translator(),
				'dateHelper' => new DateHelper(),
				'params'     => $params,
			]
		);

		$additionalVars = [
			'bookingDetails' => $details,
			'bookingLink'    => RouteHelper::getBookingRoute($booking, true),
			'bookingUid'     => $booking->uid,
			'sitename'       => $this->app->get('sitename'),
			'user'           => $this->getCurrentUser()->name
		];

		$subject = DPCalendarHelper::renderEvents([$booking], Text::_('COM_DPCALENDAR_BOOK_NOTIFICATION_SEND_SUBJECT'), null, $additionalVars);
		$body    = DPCalendarHelper::renderEvents([$booking], Text::_('COM_DPCALENDAR_BOOK_NOTIFICATION_SEND_BODY'), null, $additionalVars);

		// Send to the ticket holder
		$mailer = $this->getMailerFactory()->createMailer();
		$mailer->setSubject($subject);
		$mailer->setBody($body);
		if ($mailer instanceof Mail) {
			$mailer->IsHTML(true);
		}
		$mailer->addRecipient($booking->email);

		$fileName = Booking::getInvoice($booking, true);
		if ($fileName !== '' && $fileName !== '0') {
			$mailer->addAttachment($fileName);
		}
		$mailer->Send();
		if (file_exists($fileName)) {
			unlink($fileName);
		}

		$this->app->enqueueMessage(Text::_('COM_DPCALENDAR_CONTROLLER_SEND_SUCCESS'));

		$this->app->redirect(base64_decode($this->input->getBase64('return')));
	}

	public function save($key = null, $urlVar = 'b_id')
	{
		$data = $this->input->post->get('jform', [], 'array');

		if (empty($data['id'])) {
			$event = $this->getModel()->getEvent($data['event_id']);
			if (!$event->id) {
				return false;
			}

			$amount = [];
			if ($event->price instanceof \stdClass) {
				foreach ($event->price->value as $index => $value) {
					$amount[$index] = array_key_exists('amount', $data) ? $data['amount'] : 1;
				}
			} else {
				$amount[0] = 1;
			}

			$data['event_id'] = [$event->id => ['tickets' => $amount]];
			$this->input->post->set('jform', $data);
		}

		return parent::save($key, $urlVar);
	}

	public function edit($key = null, $urlVar = 'b_id')
	{
		return parent::edit($key, $urlVar);
	}

	public function cancel($key = 'b_id')
	{
		return parent::cancel($key);
	}
}
