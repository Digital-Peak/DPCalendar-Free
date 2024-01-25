<?php

use DPCalendar\View\BaseView;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Toolbar\ToolbarHelper;

/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2014 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

defined('_JEXEC') or die();

use Joomla\Registry\Registry;

class DPCalendarViewExtCalendars extends BaseView
{
	protected $items;
	protected $pagination;
	protected $pluginParams;

	protected function init()
	{
		$this->items        = $this->get('Items');
		$this->pagination   = $this->get('Pagination');
		$this->pluginParams = new Registry();

		$plugin = PluginHelper::getPlugin('dpcalendar', $this->input->getWord('dpplugin'));
		if ($plugin) {
			$this->pluginParams->loadString($plugin->params);
		}

		if ((is_countable($errors = $this->get('Errors')) ? count($errors = $this->get('Errors')) : 0) !== 0) {
			throw new Exception(implode("\n", $errors));
		}
	}

	protected function addToolbar()
	{
		$canDo = DPCalendarHelper::getActions();

		if ($canDo->get('core.create') && $this->input->get('import') != '') {
			ToolbarHelper::custom('extcalendars.import', 'refresh', '', 'COM_DPCALENDAR_VIEW_TOOLS_IMPORT', false);
		}
		if ($canDo->get('core.create')) {
			ToolbarHelper::addNew('extcalendar.add');
		}
		if ($canDo->get('core.edit')) {
			ToolbarHelper::editList('extcalendar.edit');
		}
		if ($canDo->get('core.edit.state')) {
			ToolbarHelper::publish('extcalendars.publish', 'JTOOLBAR_PUBLISH', true);
			ToolbarHelper::unpublish('extcalendars.unpublish', 'JTOOLBAR_UNPUBLISH', true);
		}
		if ($canDo->get('core.delete')) {
			ToolbarHelper::deleteList('', 'extcalendars.delete', 'COM_DPCALENDAR_DELETE');
		}
		if ($canDo->get('core.admin', 'com_dpcalendar')) {
			ToolbarHelper::custom('extcalendars.cacheclear', 'lightning', '', 'COM_DPCALENDAR_VIEW_EXTCALENDARS_CACHE_CLEAR_BUTTON', false);
		}
	}
}
