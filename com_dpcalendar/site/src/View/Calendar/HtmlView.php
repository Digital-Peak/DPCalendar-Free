<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2014 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Component\DPCalendar\Site\View\Calendar;

\defined('_JEXEC') or die();

use DigitalPeak\Component\DPCalendar\Administrator\Helper\DPCalendarHelper;
use DigitalPeak\Component\DPCalendar\Administrator\Model\CalendarModel;
use DigitalPeak\Component\DPCalendar\Administrator\View\BaseView;
use DigitalPeak\Component\DPCalendar\Site\View\CalendarViewTrait;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Language\Text;

class HtmlView extends BaseView
{
	use CalendarViewTrait;

	/** @var array */
	protected $items;

	/** @var array */
	protected $visibleCalendars;

	/** @var array */
	protected $hiddenCalendars;

	/** @var Form */
	protected $quickaddForm;

	/** @var array */
	protected $resources;

	/** @var string */
	protected $returnPage;

	protected function init(): void
	{
		$items = $this->getModel()->getAllItems();
		if (!$items) {
			throw new \Exception(Text::_('JGLOBAL_CATEGORY_NOT_FOUND'));
		}

		$this->items = $items;
		foreach ($items as $calendar) {
			$this->fillCalendar($calendar);
		}

		/** @var CalendarModel $calendarModel */
		$calendarModel = $this->getDPCalendar()->getMVCFactory()->createModel('Calendar', 'Administrator');

		$visibleCalendars = [];
		foreach ($this->params->get('idsdnl', []) as $id) {
			$parent = $calendarModel->getCalendar($id);
			if ($parent === null) {
				continue;
			}

			$this->fillCalendar($parent);

			if ($parent->getId() !== 'root') {
				$visibleCalendars[$parent->getId()] = $parent;
			}

			foreach ($parent->getChildren(true) as $child) {
				$visibleCalendars[$child->getId()] = $child;
			}
		}

		// If none are selected, use the calendars from the menu item
		$this->visibleCalendars = $visibleCalendars === [] ? $this->items : $visibleCalendars;
		$this->hiddenCalendars  = array_udiff($this->items, $this->visibleCalendars, fn ($c1, $c2) => $c1->getId() === $c2->getId() ? 0 : -1);

		$this->quickaddForm = $this->getModel()->getQuickAddForm($this->params);

		$this->resources = [];
		if ($this->params->get('calendar_filter_locations')
			&& $this->params->get('calendar_resource_views')
			&& $this->getLayout() == 'default'
			&& !DPCalendarHelper::isFree()) {
			// Load the model
			$model = $this->getDPCalendar()->getMVCFactory()->createModel('Locations', 'Administrator', ['ignore_request' => true]);
			$model->getState();
			$model->setState('list.limit', 10000);
			$model->setState('filter.search', 'ids:' . implode(',', $this->params->get('calendar_filter_locations')));

			// Add the locations
			foreach ($model->getItems() as $location) {
				if ($location->rooms) {
					foreach ($location->rooms as $room) {
						$this->resources[] = (object)['id' => $location->id . '-' . $room->id, 'title' => $room->title];
					}
				}
			}
		}

		// Prepare the resources for the timeline
		if (str_contains($this->getLayout(), 'timeline')) {
			$this->resources = [];
			foreach (array_merge($this->hiddenCalendars, $this->visibleCalendars) as $calendar) {
				$this->resources[] = (object)['id' => $calendar->getId() , 'title' => $calendar->getTitle()];
			}
		}

		$this->returnPage = $this->input->getInt('Itemid', 0) !== 0 ? 'index.php?Itemid=' . $this->input->getInt('Itemid', 0) : '';

		$this->prepareForm($items);

		if (\array_key_exists('start-date', $this->activeFilters)) {
			unset($this->activeFilters['start-date']);
		}

		if (\array_key_exists('end-date', $this->activeFilters)) {
			unset($this->activeFilters['end-date']);
		}

		$this->filterForm->setFieldAttribute('start-date', 'type', 'hidden', 'list');
		$this->filterForm->setFieldAttribute('end-date', 'type', 'hidden', 'list');

		parent::init();
	}
}
