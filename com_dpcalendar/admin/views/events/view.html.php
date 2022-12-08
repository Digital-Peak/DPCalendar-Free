<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2014 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */
defined('_JEXEC') or die();

use Joomla\Utilities\ArrayHelper;

class DPCalendarViewEvents extends \DPCalendar\View\BaseView
{
	protected $items;
	protected $pagination;
	protected $authors;

	public function init()
	{
		$this->setModel(JModelLegacy::getInstance('AdminEvents', 'DPCalendarModel'), true);
		$this->items      = $this->get('Items');
		$this->pagination = $this->get('Pagination');
		$this->authors    = $this->get('Authors');

		$this->displayData['format'] = $this->params->get('event_form_date_format', 'd.m.Y');

		$this->startDate = null;
		if ($this->state->get('filter.search_start')) {
			$this->startDate = DPCalendarHelper::getDateFromString(
				$this->state->get('filter.search_start'),
				null,
				true,
				$this->params->get('event_form_date_format', 'd.m.Y')
			);
		}

		$this->endDate = null;
		if ($this->state->get('filter.search_end')) {
			$this->endDate = DPCalendarHelper::getDateFromString(
				$this->state->get('filter.search_end'),
				null,
				true,
				$this->params->get('event_form_date_format', 'd.m.Y')
			);
		}
	}

	protected function addToolbar()
	{
		$state = $this->get('State');
		$canDo = DPCalendarHelper::getActions($state->get('filter.category_id'));
		$user  = JFactory::getUser();

		$bar = JToolbar::getInstance('toolbar');

		if (count($user->getAuthorisedCategories('com_dpcalendar', 'core.create')) > 0) {
			JToolbarHelper::addNew('event.add');
		}
		if ($canDo->get('core.edit')) {
			JToolbarHelper::editList('event.edit');
		}
		if ($canDo->get('core.edit.state')) {
			JToolbarHelper::divider();
			JToolbarHelper::publish('events.publish', 'JTOOLBAR_PUBLISH', true);
			JToolbarHelper::unpublish('events.unpublish', 'JTOOLBAR_UNPUBLISH', true);

			JToolbarHelper::divider();
			JToolbarHelper::archiveList('events.archive');
			JToolbarHelper::checkin('events.checkin');
		}
		if ($state->get('filter.state') == -2 && $canDo->get('core.delete')) {
			JToolbarHelper::deleteList('', 'events.delete', 'JTOOLBAR_EMPTY_TRASH');
			JToolbarHelper::divider();
		} else if ($canDo->get('core.edit.state')) {
			JToolbarHelper::trash('events.trash');
			JToolbarHelper::divider();
		}

		if ($user->authorise('core.edit') && DPCalendarHelper::isJoomlaVersion('3')) {
			$title = JText::_('JTOOLBAR_BATCH');
			$dhtml = "<button data-toggle=\"modal\" data-target=\"#collapseModal\" class=\"btn btn-small\">
			<i class=\"icon-checkbox-partial\" title=\"$title\"></i>
			$title</button>";
			$bar->appendButton('Custom', $dhtml, 'batch');
		}

		parent::addToolbar();
	}

	protected function getState($item)
	{
		$states = [
			0 => [
				'star-empty',
				'events.featured',
				'COM_DPCALENDAR_VIEW_EVENTS_UNFEATURED',
				'COM_DPCALENDAR_VIEW_EVENTS_TOGGLE_TO_FEATURE'
			],
			1 => ['star', 'events.unfeatured', 'COM_DPCALENDAR_FEATURED', 'COM_DPCALENDAR_VIEW_EVENTS_TOGGLE_TO_UNFEATURE'],
		];

		return ArrayHelper::getValue($states, (int)$item->featured, $states[1]);
	}
}
