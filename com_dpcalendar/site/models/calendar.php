<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2014 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

defined('_JEXEC') or die();

use DPCalendar\Helper\DPCalendarHelper;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Registry\Registry;

class DPCalendarModelCalendar extends ListModel
{
	public $state;
	private $items;
	private $allItems;

	protected function populateState($ordering = null, $direction = null)
	{
		$app = Factory::getApplication();

		$this->setState('filter.extension', 'com_dpcalendar');

		if (!$app->input->getString('ids', '')) {
			$this->setState('filter.parentIds', $this->state->get('parameters.menu', new Registry())->get('ids'));
			$this->setState('filter.categories', []);
		} else {
			$this->setState('filter.categories', explode(',', $app->input->getString('ids')));
			$this->setState('filter.parentIds', $this->setState('filter.categories'));
		}

		$this->setState('params', method_exists($app, 'getParams') ? $app->getParams() : ComponentHelper::getParams('com_dpcalendar'));

		$this->setState('filter.published', 1);
		$this->setState('filter.access', true);
	}

	protected function getStoreId($id = '')
	{
		// Compile the store id
		$id .= ':' . $this->getState('filter.extension');
		$id .= ':' . $this->getState('filter.published');
		$id .= ':' . $this->getState('filter.access');

		return parent::getStoreId($id);
	}

	public function getItems()
	{
		if (!$this->items) {
			$app    = Factory::getApplication();
			$menu   = $app->getMenu();
			$active = $menu->getActive();
			$params = new Registry();
			if ($active) {
				$params->loadString($active->getParams());
			}
			$this->items    = [];
			$this->allItems = [];

			foreach ((array)$this->getState('filter.parentIds', ['root']) as $calendar) {
				if ($calendar == '-1') {
					$calendar = 'root';
				}

				$parent = DPCalendarHelper::getCalendar($calendar);
				if ($parent == null) {
					continue;
				}

				if ($parent->id != 'root') {
					$this->items[$parent->id]    = $parent;
					$this->allItems[$parent->id] = $parent;
				}

				if (!$parent->external) {
					$tmp     = $parent->getChildren(true);
					$filters = $this->getState('filter.categories');
					foreach ($tmp as $child) {
						$item                      = DPCalendarHelper::getCalendar($child->id);
						$this->allItems[$item->id] = $item;

						if (!empty($filters) && !in_array($item->id, $filters)) {
							continue;
						}
						$this->items[$item->id] = $item;
					}
				}
			}

			// Add external calendars or only the private ones when not all is set
			PluginHelper::importPlugin('dpcalendar');
			$tmp = Factory::getApplication()->triggerEvent(
				'onCalendarsFetch',
				[null, in_array('-1', (array)$this->getState('filter.parentIds', ['root'])) ? null : 'cd']
			);
			if (!empty($tmp)) {
				foreach ($tmp as $tmpCalendars) {
					foreach ($tmpCalendars as $calendar) {
						$this->items[$calendar->id]    = $calendar;
						$this->allItems[$calendar->id] = $calendar;
					}
				}
			}
		}

		return $this->items;
	}

	public function getAllItems()
	{
		if (!is_array($this->allItems)) {
			$this->getItems();
		}

		return $this->allItems;
	}

	public function getQuickAddForm(Registry $params)
	{
		Form::addFormPath(JPATH_ADMINISTRATOR . '/components/com_dpcalendar/models/forms');
		Form::addFieldPath(JPATH_ADMINISTRATOR . '/components/com_dpcalendar/models/fields');

		$format = $params->get('event_form_date_format', 'd.m.Y') . ' ' . $params->get('event_form_time_format', 'H:i');
		$date   = DPCalendarHelper::getDate();

		$form = Form::getInstance('com_dpcalendar.event.quickadd', 'event', ['control' => 'jform']);
		$form->setValue('start_date', null, $date->format($format, false));

		$date->modify('+1 hour');
		$form->setValue('end_date', null, $date->format($format, false));

		$form->setFieldAttribute('start_date', 'format', $params->get('event_form_date_format', 'd.m.Y'));
		$form->setFieldAttribute('start_date', 'formatTime', $params->get('event_form_time_format', 'H:i'));
		$form->setFieldAttribute('start_date', 'formatted', true);
		$form->setFieldAttribute('end_date', 'format', $params->get('event_form_date_format', 'd.m.Y'));
		$form->setFieldAttribute('end_date', 'formatTime', $params->get('event_form_time_format', 'H:i'));
		$form->setFieldAttribute('end_date', 'formatted', true);

		$form->setFieldAttribute('start_date', 'min_time', $params->get('event_form_min_time'));
		$form->setFieldAttribute('start_date', 'max_time', $params->get('event_form_max_time'));
		$form->setFieldAttribute('end_date', 'min_time', $params->get('event_form_min_time'));
		$form->setFieldAttribute('end_date', 'max_time', $params->get('event_form_max_time'));

		// Enable to load only calendars with create permission
		$form->setFieldAttribute('catid', 'action', 'true');
		$form->setFieldAttribute('catid', 'calendar_filter', implode(',', $params->get('event_form_calendars', [])));

		// Color
		$form->setValue('color', null, $params->get('event_form_color'));
		$form->setFieldAttribute('color', 'type', 'hidden');

		return $form;
	}
}
