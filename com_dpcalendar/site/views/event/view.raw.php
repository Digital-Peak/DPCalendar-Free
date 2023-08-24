<?php

use DPCalendar\Helper\Ical;
use Joomla\CMS\MVC\View\HtmlView;

/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2014 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */
defined('_JEXEC') or die();

JLoader::import('joomla.application.component.view');

class DPCalendarViewEvent extends HtmlView
{
	public function display($tpl = null)
	{
		$event = $this->get('Item');
		if (!$event || !$event->id) {
			return false;
		}

		if ($event->original_id > 0) {
			// Download the series
			$event = $this->getModel()->getItem($event->original_id);
		}

		Ical::createIcalFromEvents([$event], true);
	}
}
