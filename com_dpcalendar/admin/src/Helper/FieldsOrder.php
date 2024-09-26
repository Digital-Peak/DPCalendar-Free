<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2014 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Component\DPCalendar\Administrator\Helper;

use DigitalPeak\Component\DPCalendar\Administrator\Model\FieldsOrderModel;
use Joomla\CMS\Application\CMSApplicationInterface;
use Joomla\CMS\Factory;
use Joomla\Registry\Registry;

\defined('_JEXEC') or die();

class FieldsOrder
{
	public static function getBookingFields(\stdClass $booking, Registry $params, CMSApplicationInterface $app): array
	{
		return self::getModel()->getBookingFields($booking, $params, $app);
	}

	public static function getTicketFields(\stdClass $ticket, Registry $params, CMSApplicationInterface $app): array
	{
		return self::getModel()->getTicketFields($ticket, $params, $app);
	}

	private static function getModel(): FieldsOrderModel
	{
		return Factory::getApplication()->bootComponent('dpcalendar')->getMVCFactory()->createModel('FieldsOrder', 'Administrator');
	}
}
