<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2018 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */
defined('_JEXEC') or die();

JLoader::import('components.com_dpcalendar.libraries.dpcalendar.view', JPATH_ADMINISTRATOR);

class DPCalendarViewLocations extends \DPCalendar\View\BaseView
{

	protected $items;

	protected $pagination;

	protected $state;

	public function init ()
	{
		$this->state = $this->get('State');
		$this->items = $this->get('Items');
		$this->pagination = $this->get('Pagination');
	}

	protected function addToolbar ()
	{
		$state = $this->get('State');
		$canDo = DPCalendarHelper::getActions();
		$user = JFactory::getUser();
		$bar = JToolbar::getInstance('toolbar');

		if ($canDo->get('core.create'))
		{
			JToolbarHelper::addNew('location.add');
		}
		if ($canDo->get('core.edit'))
		{
			JToolbarHelper::editList('location.edit');
		}
		if ($canDo->get('core.edit.state'))
		{
			JToolbarHelper::publish('locations.publish', 'JTOOLBAR_PUBLISH', true);
			JToolbarHelper::unpublish('locations.unpublish', 'JTOOLBAR_UNPUBLISH', true);

			JToolbarHelper::archiveList('locations.archive');
			JToolbarHelper::checkin('locations.checkin');
		}
		if ($state->get('filter.state') == - 2 && $canDo->get('core.delete'))
		{
			JToolbarHelper::deleteList('', 'locations.delete', 'JTOOLBAR_EMPTY_TRASH');
		}
		else if ($canDo->get('core.edit.state'))
		{
			JToolbarHelper::trash('locations.trash');
		}

		if ($canDo->get('core.edit') && DPCalendarHelper::isJoomlaVersion('3'))
		{
			JHtml::_('bootstrap.modal', 'collapseModal');
			$title = JText::_('JTOOLBAR_BATCH');
			$dhtml = "<button data-toggle=\"modal\" data-target=\"#collapseModal\" class=\"btn btn-small\">
			<i class=\"icon-checkbox-partial\" title=\"$title\"></i>
			$title</button>";
			$bar->appendButton('Custom', $dhtml, 'batch');
		}
		parent::addToolbar();
	}
}
