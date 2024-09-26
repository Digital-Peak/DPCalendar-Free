<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2014 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Component\DPCalendar\Site\View\Calendar;

defined('_JEXEC') or die();

use DigitalPeak\Component\DPCalendar\Administrator\Calendar\Calendar;
use DigitalPeak\Component\DPCalendar\Administrator\Calendar\CalendarInterface;
use DigitalPeak\Component\DPCalendar\Administrator\Helper\DPCalendarHelper;
use DigitalPeak\Component\DPCalendar\Administrator\Model\CalendarModel;
use DigitalPeak\Component\DPCalendar\Administrator\View\BaseView;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Language\Text;

class HtmlView extends BaseView
{
	/** @var array */
	protected $items;

	/** @var array */
	protected $selectedCalendars;

	/** @var array */
	protected $doNotListCalendars;

	/** @var Form */
	protected $quickaddForm;

	/** @var array */
	protected $resources;

	/** @var string */
	protected $returnPage;

	protected function init(): void
	{
		$items = $this->get('AllItems');

		if ($items === false) {
			throw new \Exception(Text::_('JGLOBAL_CATEGORY_NOT_FOUND'));
		}

		$this->items = $items;

		$selectedCalendars = [];
		foreach ($items as $calendar) {
			$selectedCalendars[] = $calendar->getId();
			$this->fillCalendar($calendar);
		}
		$this->selectedCalendars = $selectedCalendars;

		/** @var CalendarModel $calendarModel */
		$calendarModel = $this->app->bootComponent('dpcalendar')->getMVCFactory()->createModel('Calendar', 'Administrator');

		$doNotListCalendars = [];
		foreach ($this->params->get('idsdnl', []) as $id) {
			$parent = $calendarModel->getCalendar($id);
			if ($parent == null) {
				continue;
			}
			$this->fillCalendar($parent);

			if ($parent->getId() !== 'root') {
				$doNotListCalendars[$parent->getId()] = $parent;
			}

			foreach ($parent->getChildren(true) as $child) {
				$doNotListCalendars[$child->getId()] = $child;
				$this->fillCalendar($child);
			}
		}

		// If none are selected, use selected calendars
		$this->doNotListCalendars = $doNotListCalendars === [] ? $this->items : $doNotListCalendars;

		$this->quickaddForm = $this->getModel()->getQuickAddForm($this->params);

		$this->resources = [];
		if ($this->params->get('calendar_filter_locations')
			&& $this->params->get('calendar_resource_views')
			&& $this->getLayout() == 'default'
			&& !DPCalendarHelper::isFree()) {
			// Load the model
			$model = $this->app->bootComponent('dpcalendar')->getMVCFactory()->createModel('Locations', 'Administrator', ['ignore_request' => true]);
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

		if (str_contains($this->getLayout(), 'timeline')) {
			$this->resources = [];
			foreach ($this->doNotListCalendars as $calendar) {
				$this->resources[] = (object)['id' => $calendar->getId() , 'title' => $calendar->getTitle()];
			}
		}

		$this->returnPage = $this->input->getInt('Itemid', 0) !== 0 ? 'index.php?Itemid=' . $this->input->getInt('Itemid', 0) : '';

		parent::init();
	}

	private function fillCalendar(CalendarInterface $calendar): void
	{
		if (property_exists($calendar, 'event') && $calendar->event !== null || !$calendar instanceof Calendar) {
			return;
		}

		// @phpstan-ignore-next-line
		$calendar->event = new \stdClass();

		// For some plugins
		$calendar->text = $calendar->getDescription();

		$this->app->triggerEvent('onContentPrepare', ['com_dpcalendar.categories', $calendar, $calendar->getParams(), 0]);

		$results = $this->app->triggerEvent(
			'onContentAfterTitle',
			['com_dpcalendar.categories', &$calendar, &$this->params, 0]
		);
		$calendar->event->afterDisplayTitle = trim(implode("\n", $results));

		$results = $this->app->triggerEvent(
			'onContentBeforeDisplay',
			['com_dpcalendar.categories', &$calendar, &$this->params, 0]
		);
		$calendar->event->beforeDisplayContent = trim(implode("\n", $results));

		$results = $this->app->triggerEvent(
			'onContentAfterDisplay',
			['com_dpcalendar.categories', &$calendar, &$this->params, 0]
		);
		$calendar->event->afterDisplayContent = trim(implode("\n", $results));

		if ($calendar->text === '' || $calendar->text === '0') {
			return;
		}

		$calendar->setDescription($calendar->text);
	}
}
