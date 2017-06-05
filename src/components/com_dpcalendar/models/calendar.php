<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2017 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */
defined('_JEXEC') or die();

class DPCalendarModelCalendar extends JModelLegacy
{

	private $items = null;

	private $allItems = null;

	protected function populateState ()
	{
		$app = JFactory::getApplication();
		$this->setState('filter.extension', 'com_dpcalendar');

		if (JFactory::getApplication()->input->getVar('ids', null) == null)
		{
			$this->setState('filter.parentIds', $this->state->get('parameters.menu', new JRegistry())
				->get('ids'));
			$this->setState('filter.categories', array());
		}
		else
		{
			$this->setState('filter.categories', explode(',', JFactory::getApplication()->input->getVar('ids', null)));
			$this->setState('filter.parentIds', $this->setState('filter.categories'));
		}

		$params = $app->getParams();
		$this->setState('params', $params);

		$this->setState('filter.published', 1);
		$this->setState('filter.access', true);
	}

	protected function getStoreId ($id = '')
	{
		// Compile the store id.
		$id .= ':' . $this->getState('filter.extension');
		$id .= ':' . $this->getState('filter.published');
		$id .= ':' . $this->getState('filter.access');

		return parent::getStoreId($id);
	}

	public function getItems ()
	{
		if (! count($this->items))
		{
			$app = JFactory::getApplication();
			$menu = $app->getMenu();
			$active = $menu->getActive();
			$params = new JRegistry();
			if ($active)
			{
				$params->loadString($active->params);
			}
			$this->items = array();
			$this->allItems = array();

			foreach ($this->getState('filter.parentIds', array('root')) as $calendar)
			{
				$parent = DPCalendarHelper::getCalendar($calendar);
				if ($parent == null)
				{
					continue;
				}

				if ($parent->id != 'root')
				{
					$this->items[$parent->id] = $parent;
					$this->allItems[$parent->id] = $parent;
				}

				if (! $parent->external)
				{
					$tmp = $parent->getChildren(true);
					$filters = $this->getState('filter.categories');
					foreach ($tmp as $child)
					{
						$item = DPCalendarHelper::getCalendar($child->id);
						$this->allItems[$item->id] = $item;

						if (! empty($filters) && ! in_array($item->id, $filters))
						{
							continue;
						}
						$this->items[$item->id] = $item;
					}
				}
			}

			// Add caldav calendars when available
			$tmp = JDispatcher::getInstance()->trigger('onCalendarsFetch', array(
					null,
					'cd'
			));
			if (! empty($tmp))
			{
				foreach ($tmp as $tmpCalendars)
				{
					foreach ($tmpCalendars as $calendar)
					{
						$this->items[$calendar->id] = $calendar;
						$this->allItems[$calendar->id] = $calendar;
					}
				}
			}
		}

		return $this->items;
	}

	public function getAllItems ()
	{
		if (! is_array($this->allItems))
		{
			$this->getItems();
		}
		return $this->allItems;
	}
}
