<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2014 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Component\DPCalendar\Administrator\View\ExtCalendars;

defined('_JEXEC') or die();

use DigitalPeak\Component\DPCalendar\Administrator\View\BaseView;
use Joomla\CMS\Helper\ContentHelper;
use Joomla\CMS\Pagination\Pagination;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\Registry\Registry;

class HtmlView extends BaseView
{
	/** @var array */
	protected $items;

	/** @var Pagination */
	protected $pagination;

	/** @var Registry */
	protected $pluginParams;

	protected function init(): void
	{
		$this->items        = $this->get('Items');
		$this->pagination   = $this->get('Pagination');
		$this->pluginParams = new Registry();

		$plugin = PluginHelper::getPlugin('dpcalendar', $this->input->getWord('dpplugin'));
		if ($plugin) {
			$this->pluginParams->loadString($plugin->params);
		}

		if ((is_countable($errors = $this->get('Errors')) ? count($errors = $this->get('Errors')) : 0) !== 0) {
			throw new \Exception(implode("\n", $errors));
		}
	}

	protected function addToolbar(): void
	{
		$canDo = ContentHelper::getActions('com_dpcalendar');

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
