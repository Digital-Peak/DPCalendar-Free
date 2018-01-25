<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2018 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */

defined('_JEXEC') or die();

use Joomla\Registry\Registry;

class DPCalendarViewExtCalendars extends \DPCalendar\View\BaseView
{
	protected $items;
	protected $pagination;
	protected $pluginParams = null;

	public function init()
	{
		$this->items        = $this->get('Items');
		$this->pagination   = $this->get('Pagination');
		$this->pluginParams = new Registry();

		$plugin = JPluginHelper::getPlugin('dpcalendar', $this->input->getWord('dpplugin'));
		if ($plugin) {
			$this->pluginParams->loadString($plugin->params);
		}

		if (count($errors = $this->get('Errors'))) {
			throw new Exception(implode("\n", $errors));
		}
	}

	protected function addToolbar()
	{
		$canDo = DPCalendarHelper::getActions();

		if ($canDo->get('core.create') && $this->input->get('import') != '') {
			JToolbarHelper::custom('extcalendars.import', 'refresh', '', 'COM_DPCALENDAR_VIEW_TOOLS_IMPORT', false);
		}
		if ($canDo->get('core.create')) {
			JToolbarHelper::addNew('extcalendar.add');
		}
		if ($canDo->get('core.edit')) {
			JToolbarHelper::editList('extcalendar.edit');
		}
		if ($canDo->get('core.edit.state')) {
			JToolbarHelper::publish('extcalendars.publish', 'JTOOLBAR_PUBLISH', true);
			JToolbarHelper::unpublish('extcalendars.unpublish', 'JTOOLBAR_UNPUBLISH', true);
		}
		if ($canDo->get('core.delete')) {
			JToolbarHelper::deleteList('', 'extcalendars.delete', 'COM_DPCALENDAR_DELETE');
		}
		if ($canDo->get('core.admin', 'com_dpcalendar')) {
			JToolbarHelper::custom('extcalendars.cacheclear', 'lightning', '', 'COM_DPCALENDAR_VIEW_EXTCALENDARS_CACHE_CLEAR_BUTTON', false);
		}
	}
}
