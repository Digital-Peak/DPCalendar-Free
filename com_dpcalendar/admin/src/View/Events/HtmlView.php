<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2014 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Component\DPCalendar\Administrator\View\Events;

\defined('_JEXEC') or die();

use DigitalPeak\Component\DPCalendar\Administrator\View\BaseView;
use Joomla\CMS\Helper\ContentHelper;
use Joomla\CMS\Pagination\Pagination;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\Utilities\ArrayHelper;

class HtmlView extends BaseView
{
	/** @var array */
	protected $items;

	/** @var Pagination */
	protected $pagination;

	/** @var array */
	protected $authors;

	protected function init(): void
	{
		$this->setModel($this->getDPCalendar()->getMVCFactory()->createModel('Events', 'Administrator'), true);
		$this->items      = $this->get('Items');
		$this->pagination = $this->get('Pagination');
		$this->authors    = $this->get('Authors');

		$this->filterForm->removeField('location', 'filter');
		$this->filterForm->removeField('length-type', 'filter');
		$this->filterForm->removeField('radius', 'filter');
		$this->filterForm->setFieldAttribute('calendars', 'multiple', 'false', 'filter');

		if ($date = $this->state->get('list.start-date')) {
			$this->filterForm->setValue('start-date', 'list', $date);
		}

		if ($date = $this->state->get('list.end-date')) {
			$this->filterForm->setValue('end-date', 'list', $date);
		}

		// Set the date formats
		$this->filterForm->setFieldAttribute('start-date', 'format', $this->params->get('event_form_date_format', 'd.m.Y'), 'list');
		$this->filterForm->setFieldAttribute('start-date', 'formatted', '1', 'list');
		$this->filterForm->setFieldAttribute('end-date', 'format', $this->params->get('event_form_date_format', 'd.m.Y'), 'list');
		$this->filterForm->setFieldAttribute('end-date', 'formatted', '1', 'list');
	}

	protected function addToolbar(): void
	{
		$state = $this->get('State');
		$canDo = ContentHelper::getActions('com_dpcalendar');
		$user  = $this->getCurrentUser();

		if (\count($user->getAuthorisedCategories('com_dpcalendar', 'core.create')) > 0) {
			ToolbarHelper::addNew('event.add');
		}
		if ($canDo->get('core.edit')) {
			ToolbarHelper::editList('event.edit');
		}
		if ($canDo->get('core.edit.state')) {
			ToolbarHelper::divider();
			ToolbarHelper::publish('events.publish', 'JTOOLBAR_PUBLISH', true);
			ToolbarHelper::unpublish('events.unpublish', 'JTOOLBAR_UNPUBLISH', true);

			ToolbarHelper::divider();
			ToolbarHelper::archiveList('events.archive');
			ToolbarHelper::checkin('events.checkin');
		}
		if ($state->get('filter.state') == -2 && $canDo->get('core.delete')) {
			ToolbarHelper::deleteList('', 'events.delete', 'JTOOLBAR_EMPTY_TRASH');
			ToolbarHelper::divider();
		} elseif ($canDo->get('core.edit.state')) {
			ToolbarHelper::trash('events.trash');
			ToolbarHelper::divider();
		}

		parent::addToolbar();
	}

	protected function getState(\stdClass $item): string|array
	{
		$states = [
			0 => ['circle', 'events.featured', 'COM_DPCALENDAR_VIEW_EVENTS_UNFEATURED', 'COM_DPCALENDAR_VIEW_EVENTS_TOGGLE_TO_FEATURE'],
			1 => ['star', 'events.unfeatured', 'COM_DPCALENDAR_FEATURED', 'COM_DPCALENDAR_VIEW_EVENTS_TOGGLE_TO_UNFEATURE'],
		];

		return ArrayHelper::getValue($states, $item->featured, $states[1]);
	}
}
