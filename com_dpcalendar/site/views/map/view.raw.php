<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2014 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

defined('_JEXEC') or die();

use DPCalendar\Helper\DPCalendarHelper;
use DPCalendar\Helper\Location;
use DPCalendar\View\BaseView;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\Registry\Registry;

class DPCalendarViewMap extends BaseView
{
	public function display($tpl = null)
	{
		$model = BaseDatabaseModel::getInstance('Events', 'DPCalendarModel');
		$this->setModel($model, true);

		parent::display($tpl);
	}

	public function init()
	{
		$access = 0;
		$params = null;

		if ($this->input->getInt('module-id')) {
			$db    = Factory::getDbo();
			$query = $db->getQuery(true);
			$query->select('m.*');
			$query->from('#__modules AS m');
			$query->where('id = ' . $this->input->getInt('module-id'));
			$db->setQuery($query);
			$module = $db->loadObject();

			if ($module != null) {
				$params = new Registry($module->params);
				$params->set('map_view_lat', $params->get('lat'));
				$params->set('map_view_long', $params->get('long'));
				$params->set('map_date_format', $params->get('date_format', 'd.m.Y'));
				$params->set('map_expand', $params->get('expand', 1));
				$params->set('map_include_ongoing', $params->get('include_ongoing', 0));
				$access = $module->access;
			}
		} else {
			$menu   = $this->app->getMenu()->getItem($this->input->getInt('Itemid'));
			$params = $menu && !empty($menu->getParams()) ? $menu->getParams() : ComponentHelper::getParams('com_dpcalendar');
			$access = $menu && !empty($menu->access) ? $menu->access : 1;
		}

		$this->params->merge($params);

		if ($this->user->authorise('core.admin', 'com_dpcalendar') || in_array((int)$access, $this->user->getAuthorisedViewLevels())) {
			$this->getModel()->setState('parameters.menu', $this->params);
		} else {
			$this->app->enqueueMessage($this->translate('COM_DPCALENDAR_ALERT_NO_AUTH'), 'error');

			return false;
		}

		$model = BaseDatabaseModel::getInstance('Calendar', 'DPCalendarModel');
		$model->getState();
		$model->setState('filter.parentIds', $this->params->get('ids', ['root']));
		$ids = [];
		foreach ($model->getItems() as $calendar) {
			$ids[] = $calendar->id;
		}

		$this->getModel()->setState('list.limit', 1000);
		$this->getModel()->setState('category.id', $ids);
		$this->getModel()->setState('filter.expand', $this->params->get('map_expand', 1));
		$this->getModel()->setState('filter.ongoing', $this->params->get('map_include_ongoing', 0));
		$this->getModel()->setState('filter.author', $this->params->get('map_filter_author', 0));

		$context = 'com_dpcalendar.map.';

		$location = $this->app->getUserStateFromRequest($context . 'location', 'location');
		if (empty($location)) {
			$location = $this->params->get('map_view_lat', 47) . ',' . $this->params->get('map_view_long', 4);
		}

		$this->getModel()->setState('filter.location', Location::get($location, false));
		$this->getModel()->setState('filter.radius', $this->app->getUserStateFromRequest($context . 'radius', 'radius'));
		$this->getModel()->setState('filter.length-type', $this->app->getUserStateFromRequest($context . 'length-type', 'length-type'));
		$this->getModel()->setState('filter.search', $this->app->getUserStateFromRequest($context . 'search', 'search'));

		$this->handleDate('start', $this->params);
		$this->handleDate('end', $this->params);

		// Initialize variables
		$items = $this->get('Items');

		// Check for errors
		if (is_countable($errors = $this->get('Errors')) ? count($errors = $this->get('Errors')) : 0) {
			throw new Exception(implode("\n", $errors));
		}

		if ($items === false) {
			throw new Exception($this->translate('JGLOBAL_CATEGORY_NOT_FOUND'), 404);
		}

		$this->items = $items;
	}

	private function handleDate($type, $params)
	{
		$context = 'com_dpcalendar.map.';
		$date    = null;

		// Get the date from input
		$value = $this->app->input->getString($type . '-date', 'notset');

		// New date from request
		if ($value && $value != 'notset') {
			$date = DPCalendarHelper::getDateFromString($value, 0, true, $params->get('map_date_format', 'd.m.Y'));
		}

		// If not set, get it from the session
		if (!$date && $value == 'notset' && $value = $this->app->getUserState($context . 'end-date')) {
			$date = DPCalendarHelper::getDate($value);
		}

		// Transform the date when exists
		if ($date) {
			$date->setTime(23, 59, 59);
		}

		// Set the date
		$this->state->set('list.' . $type . '-date', $date);
		$this->app->setUserState($context . $type . '-date', $date ? $date->toSql() : null);
	}
}
