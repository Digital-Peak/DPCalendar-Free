<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2018 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DPCalendar\Booking\Stages;

defined('_JEXEC') or die();

use Joomla\Component\Fields\Administrator\Helper\FieldsHelper;
use League\Pipeline\StageInterface;

\JLoader::register('FieldsHelper', JPATH_ADMINISTRATOR . '/components/com_fields/helpers/fields.php');

class AdjustCustomFields implements StageInterface
{
	public function __invoke($payload)
	{
		if (empty($payload->eventsWithTickets)) {
			return $payload;
		}

		// Clear the cache, doggy
		try {
			$reflection = new \ReflectionProperty(\FieldsHelper::class, 'fieldsCache');
		} catch (\Exception $exception) {
			$reflection = new \ReflectionProperty(FieldsHelper::class, 'fieldsCache');
		}
		$reflection->setAccessible(true);
		$reflection->setValue(null, null);

		$event                   = reset($payload->eventsWithTickets);
		$payload->item->catid    = $event->catid;
		$payload->item->jcfields = \FieldsHelper::getFields('com_dpcalendar.booking', $payload->item, true);
		unset($payload->item->catid);

		return $payload;
	}
}
