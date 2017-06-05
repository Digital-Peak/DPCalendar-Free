<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2012 - 2015 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */
defined('_JEXEC') or die();

JLoader::import('components.com_dpfields.models.types.base', JPATH_ADMINISTRATOR);

class DPFieldsTypeDpcalendar extends DPFieldsTypeBase
{

	public function prepareValueForDisplay ($value, $field)
	{
		if (! is_array($value))
		{
			$value = array(
					$value
			);
		}

		JLoader::import('components.com_dpcalendar.helpers.dpcalendar', JPATH_ADMINISTRATOR);

		$texts = array();
		foreach ($value as $calendarId)
		{
			if (! $calendarId)
			{
				continue;
			}

			// Getting the calendar to add the title to display
			$calendar = DPCalendarHelper::getCalendar($calendarId);
			if (! $calendar)
			{
				continue;
			}
			$texts[] = $calendar->title;
		}
		return htmlentities(implode(', ', $texts));
	}

	protected function postProcessDomNode ($field, DOMElement $fieldNode, JForm $form)
	{
		$fieldNode->setAttribute('extension', 'com_dpcalendar');

		return parent::postProcessDomNode($field, $fieldNode, $form);
	}
}
