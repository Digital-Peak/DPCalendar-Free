<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2017 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */
defined('_JEXEC') or die();

JLoader::import('components.com_dpcalendar.libraries.dpcalendar.view', JPATH_ADMINISTRATOR);

class DPCalendarViewEvents extends \DPCalendar\View\BaseView
{

	protected $items;

	protected $pagination;

	protected $state;

	protected $authors;

	public function init ()
	{
		$this->setModel(JModelLegacy::getInstance('AdminEvents', 'DPCalendarModel'), true);
		$this->state = $this->get('State');
		$this->items = $this->get('Items');
		$this->pagination = $this->get('Pagination');
		$this->authors = $this->get('Authors');
	}

	protected function addToolbar ()
	{
		if (strpos($this->getLayout(), 'modal') !== false)
		{
			return;
		}

		$state = $this->get('State');
		$canDo = DPCalendarHelper::getActions($state->get('filter.category_id'));
		$user = JFactory::getUser();

		$bar = JToolBar::getInstance('toolbar');

		if (count($user->getAuthorisedCategories('com_dpcalendar', 'core.create')) > 0)
		{
			JToolBarHelper::addNew('event.add');
		}
		if ($canDo->get('core.edit'))
		{
			JToolBarHelper::editList('event.edit');
		}
		if ($canDo->get('core.edit.state'))
		{
			JToolBarHelper::divider();
			JToolBarHelper::publish('events.publish', 'JTOOLBAR_PUBLISH', true);
			JToolBarHelper::unpublish('events.unpublish', 'JTOOLBAR_UNPUBLISH', true);

			JToolBarHelper::divider();
			JToolBarHelper::archiveList('events.archive');
			JToolBarHelper::checkin('events.checkin');
		}
		if ($state->get('filter.state') == - 2 && $canDo->get('core.delete'))
		{
			JToolBarHelper::deleteList('', 'events.delete', 'JTOOLBAR_EMPTY_TRASH');
			JToolBarHelper::divider();
		}
		else if ($canDo->get('core.edit.state'))
		{
			JToolBarHelper::trash('events.trash');
			JToolBarHelper::divider();
		}

		if ($user->authorise('core.edit') && DPCalendarHelper::isJoomlaVersion('3'))
		{
			$title = JText::_('JTOOLBAR_BATCH');
			$dhtml = "<button data-toggle=\"modal\" data-target=\"#collapseModal\" class=\"btn btn-small\">
			<i class=\"icon-checkbox-partial\" title=\"$title\"></i>
			$title</button>";
			$bar->appendButton('Custom', $dhtml, 'batch');
		}
		parent::addToolbar();
	}
}
