<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2018 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */
defined('_JEXEC') or die();

use Joomla\Utilities\ArrayHelper;

if (!JLoader::import('components.com_dpcalendar.helpers.dpcalendar', JPATH_ADMINISTRATOR)) {
	return;
}

class PlgContentDpcalendar extends JPlugin
{

	public function onContentPrepare($context, $item, $articleParams)
	{
		// Count how many times we need to process events
		$count = substr_count($item->text, '{{#events');
		for ($i = 0; $i < $count; $i ++)
		{
			// Check for parameters
			preg_match('/{{#events\s*.*?}}/i', $item->text, $starts, PREG_OFFSET_CAPTURE);
			preg_match('/{{\/events}}/i', $item->text, $ends, PREG_OFFSET_CAPTURE);

			// Extract the parameters
			$start = $starts[0][1] + strlen($starts[0][0]);
			$end = $ends[0][1];
			$params = explode(' ', str_replace(array(
					'{{#events',
					'}}'
			), '', $starts[0][0]));

			// Load the module
			JModelLegacy::addIncludePath(JPATH_SITE . '/components/com_dpcalendar/models', 'DPCalendarModel');
			$model = JModelLegacy::getInstance('Events', 'DPCalendarModel', array(
					'ignore_request' => true
			));

			// Set some default variables
			$model->getState();
			$model->setState('filter.state', 1);
			$model->setState('filter.expand', true);
			$model->setState('list.limit', 5);

			$now = DPCalendarHelper::getDate();
			$model->setState('list.start-date', $now->format('U'));
			$now->modify('+1 year');
			$model->setState('list.end-date', $now->format('U'));

			// Loop trough the params and set them on the model
			foreach ($params as $string)
			{
				$string = trim($string);
				if (!$string)
				{
					continue;
				}

				$paramKey = null;
				$paramValue = null;
				$parts = explode('=', $string);
				if (count($parts) > 0)
				{
					$paramKey = $parts[0];
				}
				if (count($parts) > 1)
				{
					$paramValue = $parts[1];
				}

				if ($paramKey == 'calid')
				{
					$paramValue = explode(',', $paramValue);
					$model->setState('category.id', $paramValue);
				}
				if ($paramKey == 'eventid')
				{
					$model->setState('filter.search', 'id:' . $paramValue);
					$model->setState('list.start-date', 0);
					$model->setState('list.end-date', null);
				}
				if ($paramKey == 'limit')
				{
					$model->setState('list.limit', (int)$paramValue);
				}
				if ($paramKey == 'order')
				{
					$model->setState('list.ordering', $paramValue);
				}
				if ($paramKey == 'orderdir')
				{
					$model->setState('list.direction', $paramValue);
				}
				if ($paramKey == 'tagid')
				{
					$paramValue = explode(',', $paramValue);
					ArrayHelper::toInteger($paramValue);
					$model->setState('filter.tags', $paramValue);
				}
				if ($paramKey == 'featured')
				{
					$model->setState('filter.featured', $paramValue);
				}
				if ($paramKey == 'startdate')
				{
					$model->setState('list.start-date', DPCalendarHelper::getDate($paramValue));
				}
				if ($paramKey == 'enddate')
				{
					$model->setState('list.end-date', DPCalendarHelper::getDate($paramValue));
				}
				if ($paramKey == 'locationid')
				{
					$paramValue = explode(',', $paramValue);
					ArrayHelper::toInteger($paramValue);
					$model->setState('filter.locations', $paramValue);
				}
			}

			// Get the events
			$events = $model->getItems();

			// Render the output
			$output = DPCalendarHelper::renderEvents($events, '{{#events}}' . substr($item->text, $start, $end - $start) . '{{/events}}');

			// Set the output on the item
			$item->text = substr_replace($item->text, $output, $starts[0][1], $end + 11 - $starts[0][1]);
		}
		return true;
	}
}
