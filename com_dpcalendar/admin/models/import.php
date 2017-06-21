<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2017 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */
defined('_JEXEC') or die();

JLoader::import('joomla.application.component.modellist');

class DPCalendarModelImport extends JModelLegacy
{

	public function import ()
	{
		JPluginHelper::importPlugin('dpcalendar');
		JModelLegacy::addIncludePath(JPATH_ADMINISTRATOR . DS . 'components' . DS . 'com_categories' . DS . 'models');
		JModelLegacy::addTablePath(JPATH_ADMINISTRATOR . DS . 'components' . DS . 'com_categories' . DS . 'tables');

		JFactory::getApplication()->input->set('extension', 'com_dpcalendar');
		JFactory::getApplication()->input->post->set('extension', 'com_dpcalendar');

		$tmp = JFactory::getApplication()->triggerEvent('onCalendarsFetch');
		$calendars = array();
		if (! empty($tmp))
		{
			foreach ($tmp as $tmpCalendars)
			{
				foreach ($tmpCalendars as $calendar)
				{
					$calendars[] = $calendar;
				}
			}
		}

		$calendarsToimport = JFactory::getApplication()->input->getVar('calendar', array());
		$existingCalendars = JModelLegacy::getInstance('Categories', 'CategoriesModel')->getItems();
		$start = DPCalendarHelper::getDate(JFactory::getApplication()->input->getCmd('filter_search_start', null));
		$end = DPCalendarHelper::getDate(JFactory::getApplication()->input->getCmd('filter_search_end', null));

		$msgs = array();
		foreach ($calendars as $cal)
		{
			if (! in_array($cal->id, $calendarsToimport))
			{
				continue;
			}

			$category = null;
			foreach ($existingCalendars as $exCal)
			{
				if ($exCal->title == $cal->title)
				{
					$category = $exCal;
					break;
				}
			}

			if ($category == null)
			{
				$data = array();
				$data['id'] = 0;
				$data['title'] = $cal->title;
				$data['description'] = $cal->description;
				$data['extension'] = 'com_dpcalendar';
				$data['parent_id'] = 1;
				$data['published'] = 1;
				$data['language'] = '*';

				$model = JModelLegacy::getInstance('Category', 'CategoriesModel');
				$model->save($data);
				$category = $model->getItem($model->getState('category.id'));
			}

			$tmp = JFactory::getApplication()->triggerEvent('onEventsFetch',
					array(
							$cal->id,
							$start,
							$end,
							new JRegistry(array(
									'expand' => false
							))
					));

			$counter = 0;
			$counterUpdated = 0;
			if (! empty($tmp))
			{
				foreach ($tmp as $events)
				{
					foreach ($events as $event)
					{
						$filter = strtolower(JFactory::getApplication()->input->getVar('filter_search', ''));
						if (! empty($filter) && strpos(
								strtolower($event->title . ' ' . $event->description . ' ' . $event->url . ' ' . $event->location), $filter) === false)
						{
							continue;
						}

						$eventData = (array) $event;

						if (! isset($event->locations))
						{
							$event->locations = array();
						}
						$eventData['location_ids'] = array_map(function  ($l) {
							return $l->id;
						}, $event->locations);

						// Setting the reference to the old event
						$xreference = $eventData['id'];
						$eventData['xreference'] = $xreference;

						unset($eventData['id']);
						unset($eventData['locations']);
						$eventData['alias'] = ! empty($event->alias) ? $event->alias : JApplicationHelper::stringURLSafe($event->title);
						$eventData['catid'] = $category->id;

						// Find an existing event with the same xreference
						$table = JTable::getInstance('Event', 'DPCalendarTable');
						$table->load(array(
								'xreference' => $xreference
						));
						if ($table->id)
						{
							$eventData['id'] = $table->id;
						}
						JModelLegacy::addIncludePath(JPATH_SITE . '/components/com_dpcalendar/models', 'DPCalendarModel');
						$model = JModelLegacy::getInstance('Form', 'DPCalendarModel');
						$model->getState();
						if (! $model->save($eventData))
						{
							JError::raiseWarning(0, $model->getError());
						}
						else
						{
							if ($eventData['id'])
							{
								$counterUpdated ++;
							}
							else
							{
								$counter ++;
							}
						}
						$model->detach();
					}
				}
			}
			$msgs[] = sprintf(JText::_('COM_DPCALENDAR_N_ITEMS_CREATED'), $counter, $cal->title);
			$msgs[] = sprintf(JText::_('COM_DPCALENDAR_N_ITEMS_UPDATED'), $counterUpdated, $cal->title);
		}
		$this->set('messages', $msgs);
	}

	public function getTable ($type = 'Location', $prefix = 'DPCalendarTable', $config = array())
	{
		return JTable::getInstance($type, $prefix, $config);
	}
}
