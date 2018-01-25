<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2018 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */
defined('_JEXEC') or die();

if (!JLoader::import('components.com_dpcalendar.helpers.dpcalendar', JPATH_ADMINISTRATOR)) {
	return;
}

class PlgSearchDPCalendar extends JPlugin
{

	public function __construct(& $subject, $config)
	{
		parent::__construct($subject, $config);
		$this->loadLanguage();
	}

	public function onContentSearchAreas()
	{
		static $areas = array('dpcalendar' => 'PLG_SEARCH_DPCALENDAR_EVENTS');

		return $areas;
	}

	public function onContentSearch($text, $phrase = '', $ordering = '', $areas = null)
	{
		$searchText = $text;
		if (is_array($areas)) {
			if (!array_intersect($areas, array_keys($this->onContentSearchAreas()))) {
				return array();
			}
		}

		$text = trim($text);
		if ($text == '') {
			return array();
		}

		JFactory::getLanguage()->load('com_dpcalendar', JPATH_ADMINISTRATOR . '/components/com_dpcalendar');
		JLoader::import('joomla.application.component.model');
		JModelLegacy::addIncludePath(JPATH_SITE . '/components/com_dpcalendar/models', 'DPCalendarModel');

		$model = JModelLegacy::getInstance('Events', 'DPCalendarModel', array('ignore_request' => true));
		$model->getState();
		$model->setState('list.limit', $this->params->def('search_limit', 50));
		$model->setState('category.id', 'root');
		$model->setState('category.recursive', true);
		$model->setState('filter.ongoing', 1);
		$model->setState('filter.expand', true);

		if ($this->params->def('pastevents', 1)) {
			$model->setState('list.start-date', 0);
		} else {
			$model->setState('list.start-date', DPCalendarHelper::getDate()->format('U'));
		}
		$model->setState('list.end-date', null);

		$state = array();
		if ($this->params->get('search_content', 1)) {
			$state[] = 1;
		}
		if ($this->params->get('search_archived', 1)) {
			$state[] = 2;
		}

		if (empty($state)) {
			return array();
		}

		$model->setState('filter.state', $state);

		switch ($ordering) {
			case 'oldest':
				$model->setState('list.ordering', 'a.start_date');
				$model->setState('list.direction', 'asc');
				break;
			case 'popular':
				$model->setState('list.ordering', 'a.hits');
				$model->setState('list.direction', 'desc');
				break;
			case 'alpha':
				$model->setState('list.ordering', 'a.title');
				$model->setState('list.direction', 'asc');
				break;
			case 'category':
				$model->setState('list.ordering', 'c.title');
				$model->setState('list.direction', 'asc');
				break;
			case 'newest':
			default:
				$model->setState('list.ordering', 'a.start_date');
				$model->setState('list.direction', 'desc');
				break;
		}

		$model->setState('filter.search', $text);

		$events = $model->getItems();

		foreach ($events as $key => $item) {
			$events[$key]->section    = DPCalendarHelper::getCalendar($item->catid)->title;
			$events[$key]->browsernav = $item->title;
			$events[$key]->href       = DPCalendarHelperRoute::getEventRoute($item->id, $item->catid);

			$events[$key]->text = $item->title . '<br/>' . JText::_('COM_DPCALENDAR_DATE') . ' ' .
				DPCalendarHelper::getDateStringFromEvent($item, $this->params->get('date_format'), $this->params->get('time_format'));
		}

		return $events;
	}
}
