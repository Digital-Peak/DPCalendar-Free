<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2018 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Component\DPCalendar\Administrator\Booking\Stages;

\defined('_JEXEC') or die();

use Joomla\Component\Fields\Administrator\Helper\FieldsHelper;
use League\Pipeline\StageInterface;

class AdjustCustomFields implements StageInterface
{
	public function __invoke($payload)
	{
		if (empty($payload->item) || empty($payload->eventsWithTickets)) {
			return $payload;
		}

		// Clear the cache, doggy
		$reflection = new \ReflectionProperty(FieldsHelper::class, 'fieldsCache');
		$reflection->setAccessible(true);
		$reflection->setValue(null, null);

		$event                   = reset($payload->eventsWithTickets);
		$payload->item->catid    = $event->catid;
		$payload->item->jcfields = FieldsHelper::getFields('com_dpcalendar.booking', $payload->item, true);
		unset($payload->item->catid);

		return $payload;
	}
}
