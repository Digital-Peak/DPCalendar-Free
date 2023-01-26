<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2016 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

defined('_JEXEC') or die();

use DPCalendar\View\BaseView;
use Joomla\CMS\Application\ApplicationHelper;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

class DPCalendarViewLocations extends BaseView
{
	public function display($tpl = null)
	{
		BaseDatabaseModel::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_dpcalendar/models');
		$model = BaseDatabaseModel::getInstance('Locations', 'DPCalendarModel');
		$this->setModel($model, true);

		return parent::display($tpl);
	}

	public function init()
	{
		$ids = $this->params->get('ids');
		if ($ids && !in_array(-1, $ids)) {
			$this->getModel()->setState('filter.search', 'ids:' . implode(',', $ids));
		}

		$this->getModel()->setState('filter.tags', $this->params->get('locations_filter_tags'));
		$this->getModel()->setState('filter.author', $this->params->get('locations_filter_author', 0));
		$this->getModel()->setState('list.limit', 1000);
		$this->getModel()->setState('filter.state', 1);
		$this->getModel()->setState('list.ordering', 'a.ordering');

		$this->resources = [];
		$locationGroups  = [];
		foreach ($this->get('Items') as $location) {
			// Set the grouping id
			$id = $this->params->get('locations_output_grouping', 0) ? $location->{$this->params->get('locations_output_grouping', 0)} : 0;
			$id = ApplicationHelper::stringURLSafe($id);
			if (!array_key_exists($id, $locationGroups)) {
				$locationGroups[$id] = [];
			}
			$locationGroups[$id][] = $location;

			// Determine the rooms
			$rooms = [];
			if ($location->rooms) {
				foreach ($location->rooms as $room) {
					$rooms[] = (object)['id' => $location->id . '-' . $room->id, 'title' => $room->title];
				}
			}

			$this->resources[] = (object)['id' => $location->id, 'title' => $location->title, 'children' => $rooms];
		}

		// Sort the location groups
		uksort($locationGroups, function ($id1, $id2) use ($locationGroups) {
			// Handle countries special
			if ($this->params->get('locations_output_grouping') != 'country') {
				return strcmp($id1, $id2);
			}

			return strcmp($locationGroups[$id1][0]->country_code_value, $locationGroups[$id2][0]->country_code_value);
		});
		$this->locationGroups = $locationGroups;

		$this->events = [];
		$this->ids    = [];
		if ($this->params->get('locations_show_upcoming_events', 1)) {
			$model = BaseDatabaseModel::getInstance('Calendar', 'DPCalendarModel');
			$model->getState();
			$model->setState('filter.parentIds', ['root']);

			foreach ($model->getItems() as $calendar) {
				$this->ids[] = $calendar->id;
			}

			$model = BaseDatabaseModel::getInstance('Events', 'DPCalendarModel', ['ignore_request' => true]);
			$model->setState('list.limit', 25);
			$model->setState('list.start-date', DPCalendarHelper::getDate());
			$model->setState('list.ordering', 'start_date');
			$model->setState('filter.expand', $this->params->get('locations_expand_events', 1));
			$model->setState('filter.ongoing', true);
			$model->setState('filter.state', [1, 3]);
			$model->setState('filter.language', $this->app->getLanguage()->getTag());
			$model->setState('filter.locations', $this->params->get('ids'));
			$this->events = $model->getItems();
		}

		$this->returnPage = $this->input->getInt('Itemid', null) ? 'index.php?Itemid=' . $this->input->getInt('Itemid', null) : null;
	}
}
