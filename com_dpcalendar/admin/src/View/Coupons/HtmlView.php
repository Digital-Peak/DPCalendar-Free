<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2020 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Component\DPCalendar\Administrator\View\Coupons;

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
			ToolbarHelper::addNew('coupon.add');
		}
		if ($canDo->get('core.edit')) {
			ToolbarHelper::editList('coupon.edit');
		}
		if ($canDo->get('core.edit.state')) {
			ToolbarHelper::publish('coupons.publish', 'JTOOLBAR_PUBLISH', true);
			ToolbarHelper::unpublish('coupons.unpublish', 'JTOOLBAR_UNPUBLISH', true);

			ToolbarHelper::archiveList('coupons.archive');
			ToolbarHelper::checkin('coupons.checkin');
		}
		if ($state->get('filter.state') == -2 && $canDo->get('core.delete')) {
			ToolbarHelper::deleteList('', 'coupons.delete', 'JTOOLBAR_EMPTY_TRASH');
		} elseif ($canDo->get('core.edit.state')) {
			ToolbarHelper::trash('coupons.trash');
		}
		parent::addToolbar();
	}
}
