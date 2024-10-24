<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2014 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Component\DPCalendar\Site\Model;

\defined('_JEXEC') or die();

use DigitalPeak\Component\DPCalendar\Administrator\Calendar\CalendarInterface;
use DigitalPeak\Component\DPCalendar\Administrator\Helper\DPCalendarHelper;
use Joomla\CMS\Application\SiteApplication;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Form\FormFactoryAwareInterface;
use Joomla\CMS\Form\FormFactoryAwareTrait;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Registry\Registry;

class CalendarModel extends ListModel implements FormFactoryAwareInterface
{
	use FormFactoryAwareTrait;

	protected $filterFormName = 'filter_events';

	private ?array $items    = null;
	private ?array $allItems = null;

	protected function populateState($ordering = null, $direction = null)
	{
		$app = Factory::getApplication();

		parent::populateState($ordering, $direction);

		$this->setState('filter.extension', 'com_dpcalendar');

		if (!$app->getInput()->getString('ids', '')) {
			$this->setState('filter.parentIds', $this->state->get('parameters.menu', new Registry())->get('ids'));
			$this->setState('filter.categories', []);
		} else {
			$this->setState('filter.categories', explode(',', (string)$app->getInput()->getString('ids')));
			$this->setState('filter.parentIds', $this->setState('filter.categories'));
		}

		$this->setState('params', $app instanceof SiteApplication ? $app->getParams() : ComponentHelper::getParams('com_dpcalendar'));

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

	public function getItems(): array
	{
		if ($this->items === null) {
			$this->items    = [];
			$this->allItems = [];

			$model = $this->bootComponent('dpcalendar')->getMVCFactory()->createModel('Calendar', 'Administrator');
			foreach ((array)$this->getState('filter.parentIds', ['root']) as $calendar) {
				if ($calendar == '-1') {
					$calendar = 'root';
				}

				$parent = $model->getCalendar($calendar);
				if ($parent == null) {
					continue;
				}

				if ($parent->getId() !== 'root') {
					$this->items[$parent->getId()]    = $parent;
					$this->allItems[$parent->getId()] = $parent;
				}

				$tmp     = $parent->getChildren(true);
				$filters = $this->getState('filter.categories');
				foreach ($tmp as $child) {
					$item = $model->getCalendar($child->getId());
					if (!$item instanceof CalendarInterface) {
						continue;
					}

					$this->allItems[$item->getId()] = $item;

					if (!empty($filters) && !\in_array($item->getId(), $filters)) {
						continue;
					}
					$this->items[$item->getId()] = $item;
				}
			}

			// Add external calendars or only the private ones when not all is set
			PluginHelper::importPlugin('dpcalendar');
			$tmp = Factory::getApplication()->triggerEvent(
				'onCalendarsFetch',
				[null, \in_array('-1', (array)$this->getState('filter.parentIds', ['root'])) ? null : 'cd']
			);
			if (!empty($tmp)) {
				foreach ($tmp as $tmpCalendars) {
					foreach ($tmpCalendars as $calendar) {
						$this->items[$calendar->getId()]    = $calendar;
						$this->allItems[$calendar->getId()] = $calendar;
					}
				}
			}
		}

		return $this->items;
	}

	public function getAllItems(): array
	{
		if ($this->allItems === null) {
			$this->getItems();
		}

		return $this->allItems !== null && $this->allItems !== [] ? $this->allItems : [];
	}

	public function getQuickAddForm(Registry $params): Form
	{
		$format = $params->get('event_form_date_format', 'd.m.Y') . ' ' . $params->get('event_form_time_format', 'H:i');
		$date   = DPCalendarHelper::getDate();

		$form = $this->getFormFactory()->createForm('com_dpcalendar.event.quickadd', ['control' => 'jform']);
		$form->loadFile(JPATH_ADMINISTRATOR . '/components/com_dpcalendar/forms/event.xml');
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
