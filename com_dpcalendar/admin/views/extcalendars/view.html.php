<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2017 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */
defined('_JEXEC') or die();

class DPCalendarViewExtCalendars extends JViewLegacy
{

	protected $items;

	protected $pagination;

	protected $state;

	protected $icon = '';

	protected $title = '';

	protected $pluginParams = null;

	public function display ($tpl = null)
	{
		$this->state = $this->get('State');
		$this->items = $this->get('Items');
		$this->pagination = $this->get('Pagination');
		$this->pluginParams = new JRegistry();

		$plugin = JPluginHelper::getPlugin('dpcalendar', 'dpcalendar_' . JFactory::getApplication()->input->getWord('dpplugin'));
		if ($plugin)
		{
			$this->pluginParams->loadString($plugin->params);
		}

		if (count($errors = $this->get('Errors')))
		{
			JError::raiseError(500, implode("\n", $errors));
			return false;
		}

		$this->addToolbar();
		parent::display($tpl);
	}

	protected function addToolbar ()
	{
		$canDo = DPCalendarHelper::getActions();

		if (empty($this->title))
		{
			$this->title = 'COM_DPCALENDAR_MANAGER_' . strtoupper($this->getName());
		}
		if (empty($this->icon))
		{
			$this->icon = strtolower($this->getName());
		}
		JToolbarHelper::title(JText::_($this->title), $this->icon);
		$document = JFactory::getDocument();
		$document->addStyleDeclaration(
				'.icon-48-' . $this->icon . ' {background-image: url(../media/com_dpcalendar/images/admin/48-' . $this->icon .
						 '.png);background-repeat: no-repeat;}');

		$state = $this->get('State');
		$canDo = DPCalendarHelper::getActions();
		$user = JFactory::getUser();
		$bar = JToolbar::getInstance('toolbar');

		if ($canDo->get('core.create') && JFactory::getApplication()->input->get('import') != '')
		{
			JToolbarHelper::custom('extcalendars.import', 'refresh', '', 'COM_DPCALENDAR_VIEW_TOOLS_IMPORT', false);
		}
		if ($canDo->get('core.create'))
		{
			JToolbarHelper::addNew('extcalendar.add');
		}
		if ($canDo->get('core.edit'))
		{
			JToolbarHelper::editList('extcalendar.edit');
		}
		if ($canDo->get('core.edit.state'))
		{
			JToolbarHelper::publish('extcalendars.publish', 'JTOOLBAR_PUBLISH', true);
			JToolbarHelper::unpublish('extcalendars.unpublish', 'JTOOLBAR_UNPUBLISH', true);
		}
		if ($canDo->get('core.delete'))
		{
			JToolbarHelper::deleteList('', 'extcalendars.delete', 'COM_DPCALENDAR_DELETE');
		}
		if ($canDo->get('core.admin', 'com_dpcalendar'))
		{
			JToolbarHelper::custom('extcalendars.cacheclear', 'lightning', '', 'COM_DPCALENDAR_VIEW_EXTCALENDARS_CACHE_CLEAR_BUTTON', false);
		}
	}
}
