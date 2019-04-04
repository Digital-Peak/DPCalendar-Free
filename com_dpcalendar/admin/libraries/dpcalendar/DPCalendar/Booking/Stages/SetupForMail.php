<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2018 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */

namespace DPCalendar\Booking\Stages;

defined('_JEXEC') or die();

use DPCalendar\Helper\DPCalendarHelper;
use League\Pipeline\StageInterface;

class SetupForMail implements StageInterface
{
	/**
	 * @var \JApplicationCms
	 */
	private $application = null;

	public function __construct(\JApplicationCms $application)
	{
		$this->application = $application;
	}

	public function __invoke($payload)
	{
		$this->application->getLanguage()->load('com_dpcalendar', JPATH_ADMINISTRATOR . '/components/com_dpcalendar');

		// Create the booking details for mail notification
		$params = clone \JComponentHelper::getParams('com_dpcalendar');
		$params->set('show_header', false);

		$details = DPCalendarHelper::renderLayout(
			'booking.invoice',
			array(
				'booking'    => $payload->item,
				'tickets'    => $payload->tickets,
				'translator' => new \DPCalendar\Translator\Translator(),
				'dateHelper' => new \DPCalendar\Helper\DateHelper(),
				'params'     => $params
			)
		);

		$payload->mailVariables = array(
			'bookingDetails' => $details,
			'bookingLink'    => \DPCalendarHelperRoute::getBookingRoute($payload->item, true),
			'bookingUid'     => $payload->item->uid,
			'sitename'       => $this->application->get('sitename'),
			'user'           => $payload->item->name,
			'tickets'        => $payload->tickets,
			'countTickets'   => count($payload->tickets)
		);

		foreach ($payload->item->jcfields as $field) {
			$payload->mailVariables['field-' . $field->name] = $field;
		}

		// Show the logo and address in the tickets
		$params->set('show_header', true);

		$payload->mailParams = $params;

		return $payload;
	}
}
