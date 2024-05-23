<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2014 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Component\DPCalendar\Site\View\Event;

defined('_JEXEC') or die();

use DigitalPeak\Component\DPCalendar\Administrator\View\BaseView;
use Joomla\CMS\Factory;

class RawView extends BaseView
{
	public function display($tpl = null): void
	{
		$event = $this->get('Item');
		if (!$event || !$event->id) {
			return;
		}

		if ($event->original_id > 0) {
			// Download the series
			$event = $this->getModel()->getItem($event->original_id);
		}

		Factory::getApplication()->bootComponent('dpcalendar')->getMVCFactory()->createModel('Ical', 'Administrator')->createIcalFromEvents([$event], true);
	}
}
