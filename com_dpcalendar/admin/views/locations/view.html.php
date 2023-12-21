<?php

use DPCalendar\View\BaseView;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Toolbar\Toolbar;
use Joomla\CMS\Toolbar\ToolbarHelper;

/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2014 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */
defined('_JEXEC') or die();

class DPCalendarViewLocations extends BaseView
{
	protected $items;
	protected $pagination;
	protected $state;

	public function init()
	{
		$this->state      = $this->get('State');
		$this->items      = $this->get('Items');
		$this->pagination = $this->get('Pagination');
	}

	protected function addToolbar()
	{
		$state = $this->get('State');
		$canDo = DPCalendarHelper::getActions();
		$user  = Factory::getUser();
		$bar   = Toolbar::getInstance('toolbar');

		if ($canDo->get('core.create')) {
			ToolbarHelper::addNew('location.add');
		}
		if ($canDo->get('core.edit')) {
			ToolbarHelper::editList('location.edit');
		}
		if ($canDo->get('core.edit.state')) {
			ToolbarHelper::publish('locations.publish', 'JTOOLBAR_PUBLISH', true);
			ToolbarHelper::unpublish('locations.unpublish', 'JTOOLBAR_UNPUBLISH', true);

			ToolbarHelper::archiveList('locations.archive');
			ToolbarHelper::checkin('locations.checkin');
		}
		if ($state->get('filter.state') == -2 && $canDo->get('core.delete')) {
			ToolbarHelper::deleteList('', 'locations.delete', 'JTOOLBAR_EMPTY_TRASH');
		} elseif ($canDo->get('core.edit.state')) {
			ToolbarHelper::trash('locations.trash');
		}

		if ($canDo->get('core.edit') && DPCalendarHelper::isJoomlaVersion('3')) {
			HTMLHelper::_('bootstrap.modal', 'collapseModal');
			$title = Text::_('JTOOLBAR_BATCH');
			$dhtml = "<button data-toggle=\"modal\" data-target=\"#collapseModal\" class=\"btn btn-small\">
			<i class=\"icon-checkbox-partial\" title=\"$title\"></i>
			$title</button>";
			$bar->appendButton('Custom', $dhtml, 'batch');
		}
		parent::addToolbar();
	}
}
