<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2014 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Component\DPCalendar\Administrator\View\Locations;

\defined('_JEXEC') or die();

use DigitalPeak\Component\DPCalendar\Administrator\View\BaseView;
use Joomla\CMS\Helper\ContentHelper;
use Joomla\CMS\Pagination\Pagination;
use Joomla\CMS\Toolbar\ToolbarHelper;

class HtmlView extends BaseView
{
	/** @var array */
	protected $items;

	/** @var Pagination */
	protected $pagination;

	protected function init(): void
	{
		$this->state      = $this->get('State');
		$this->items      = $this->get('Items');
		$this->pagination = $this->get('Pagination');
	}

	protected function addToolbar(): void
	{
		$state = $this->get('State');
		$canDo = ContentHelper::getActions('com_dpcalendar');

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

		parent::addToolbar();
	}
}
