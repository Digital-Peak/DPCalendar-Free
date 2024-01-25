<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2014 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

defined('_JEXEC') or die();

use DPCalendar\Helper\DPCalendarHelper;
use DPCalendar\View\BaseView;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Toolbar\Toolbar;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\Utilities\ArrayHelper;

class DPCalendarViewEvents extends BaseView
{
	public $startDate;
	public $endDate;
	protected $items;
	protected $pagination;
	protected $authors;

	protected function init()
	{
		$this->setModel(BaseDatabaseModel::getInstance('AdminEvents', 'DPCalendarModel'), true);
		$this->items      = $this->get('Items');
		$this->pagination = $this->get('Pagination');
		$this->authors    = $this->get('Authors');

		$this->filterForm->removeField('location', 'filter');
		$this->filterForm->removeField('length-type', 'filter');
		$this->filterForm->removeField('radius', 'filter');
		$this->filterForm->setFieldAttribute('calendars', 'multiple', 'false', 'filter');

		$this->startDate = null;
		if ($date = $this->state->get('list.start-date')) {
			$this->filterForm->setValue('start-date', 'list', $date);
		}

		$this->endDate = null;
		if ($date = $this->state->get('list.end-date')) {
			$this->filterForm->setValue('end-date', 'list', $date);
		}

		// Set the date formats
		$this->filterForm->setFieldAttribute('start-date', 'format', $this->params->get('event_form_date_format', 'd.m.Y'), 'list');
		$this->filterForm->setFieldAttribute('start-date', 'formatted', '1', 'list');
		$this->filterForm->setFieldAttribute('end-date', 'format', $this->params->get('event_form_date_format', 'd.m.Y'), 'list');
		$this->filterForm->setFieldAttribute('end-date', 'formatted', '1', 'list');
	}

	protected function addToolbar()
	{
		$state = $this->get('State');
		$canDo = \DPCalendarHelper::getActions();
		$user  = Factory::getUser();

		$bar = Toolbar::getInstance('toolbar');

		if ((is_countable($user->getAuthorisedCategories('com_dpcalendar', 'core.create')) ? count($user->getAuthorisedCategories('com_dpcalendar', 'core.create')) : 0) > 0) {
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

		if ($user->authorise('core.edit') && DPCalendarHelper::isJoomlaVersion('3')) {
			$title = Text::_('JTOOLBAR_BATCH');
			$dhtml = "<button data-toggle=\"modal\" data-target=\"#collapseModal\" class=\"btn btn-small\">
			<i class=\"icon-checkbox-partial\" title=\"{$title}\"></i>
			{$title}</button>";
			$bar->appendButton('Custom', $dhtml, 'batch');
		}

		parent::addToolbar();
	}

	protected function getState($item)
	{
		$states = [
			0 => ['circle', 'events.featured', 'COM_DPCALENDAR_VIEW_EVENTS_UNFEATURED', 'COM_DPCALENDAR_VIEW_EVENTS_TOGGLE_TO_FEATURE'],
			1 => ['star', 'events.unfeatured', 'COM_DPCALENDAR_FEATURED', 'COM_DPCALENDAR_VIEW_EVENTS_TOGGLE_TO_UNFEATURE'],
		];

		return ArrayHelper::getValue($states, (int)$item->featured, $states[1]);
	}
}
