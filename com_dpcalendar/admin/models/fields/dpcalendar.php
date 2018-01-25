<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2018 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */
defined('_JEXEC') or die();

JFormHelper::loadFieldClass('category');

class JFormFieldDPCalendar extends JFormFieldCategory
{

	public $type = 'DPCalendar';

	protected function getOptions ()
	{
		$options = parent::getOptions();

		JPluginHelper::importPlugin('dpcalendar');
		$tmp = JFactory::getApplication()->triggerEvent('onCalendarsFetch');
		if (! empty($tmp))
		{
			foreach ($tmp as $calendars)
			{
				foreach ($calendars as $calendar)
				{
					// Don't show caldav calendars
					if (strpos($calendar->id, 'cd-') === 0)
					{
						continue;
					}
					$options[] = JHtml::_('select.option', $calendar->id, $calendar->title);
				}
			}
		}

		return $options;
	}
}
