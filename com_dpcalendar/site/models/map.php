<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2017 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */
defined('_JEXEC') or die();

JLoader::import('joomla.application.component.modellist');

class DPCalendarModelMap extends JModelList
{

	public function __construct($config = array())
	{
		if (empty($config['filter_fields']))
		{
			$config['filter_fields'] = array(
					'id',
					'a.id',
					'title',
					'a.title',
					'hits',
					'a.hits'
			);
		}

		parent::__construct($config);
	}

	public function getItems()
	{
		$model = JModelLegacy::getInstance('Events', 'DPCalendarModel');
		$model->getState();
		$model->setState('category.id', $this->getState('category.ids', $this->getState('parameters.menu')
			->get('ids', array())));
		$model->setState('category.recursive', true);
		$model->setState('list.limit', 1000);
		$model->setState('list.start-date', DPCalendarHelper::getDate()->format('U'));
		$model->setState('list.ordering', 'start_date');

		$menuParams = $this->state->get('parameters.menu');
		$location = $this->getState('filter.location');
		if (empty($location))
		{
			$location = $menuParams->get('map_view_lat', 47) . ',' . $menuParams->get('map_view_long', 4);
		}
		$model->setState('filter.location', $location);
		$model->setState('filter.radius', $this->getState('filter.radius'));
		$model->setState('filter.length_type', $this->getState('filter.length_type'));

		return $model->getItems();
	}

	protected function populateState($ordering = null, $direction = null)
	{
		// Initialise variables.
		$app = JFactory::getApplication('site');
		$params = $app->getParams();

		$menuParams = $this->state->get('parameters.menu');
		$this->setState('category.ids',
				$app->getUserStateFromRequest('com_dpcalendar.map.filter.ids', 'ids',
						!empty($menuParams) ? $menuParams->get('ids', array()) : array(), 'array'));
		$this->setState('filter.location', $app->getUserStateFromRequest('com_dpcalendar.map.filter.location', 'filter-location'));
		$this->setState('filter.radius',
				$app->getUserStateFromRequest('com_dpcalendar.map.filter.radius', 'filter-radius', $params->get('map_view_radius')));
		$this->setState('filter.length_type',
				$app->getUserStateFromRequest('com_dpcalendar.map.filter.length_type', 'filter-length_type', $params->get('map_view_length_type')));

		// Load the parameters.
		$this->setState('params', $params);
	}
}
