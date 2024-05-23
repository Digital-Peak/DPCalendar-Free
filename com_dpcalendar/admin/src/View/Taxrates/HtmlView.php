<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2019 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Component\DPCalendar\Administrator\View\Taxrates;

defined('_JEXEC') or die();

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
		$this->items      = $this->get('Items');
		$this->pagination = $this->get('Pagination');
	}

	protected function addToolbar(): void
	{
		$canDo = ContentHelper::getActions('com_dpcalendar');

		if ($canDo->get('core.create')) {
			ToolbarHelper::addNew('taxrate.add');
		}
		if ($canDo->get('core.edit')) {
			ToolbarHelper::editList('taxrate.edit');
		}
		if ($canDo->get('core.edit.state')) {
			ToolbarHelper::publish('taxrates.publish', 'JTOOLBAR_PUBLISH', true);
			ToolbarHelper::unpublish('taxrates.unpublish', 'JTOOLBAR_UNPUBLISH', true);

			ToolbarHelper::archiveList('taxrates.archive');
			ToolbarHelper::checkin('taxrates.checkin');
		}
		if ($this->state->get('filter.state') == -2 && $canDo->get('core.delete')) {
			ToolbarHelper::deleteList('', 'taxrates.delete', 'JTOOLBAR_EMPTY_TRASH');
		} elseif ($canDo->get('core.edit.state')) {
			ToolbarHelper::trash('taxrates.trash');
		}

		parent::addToolbar();
	}
}
