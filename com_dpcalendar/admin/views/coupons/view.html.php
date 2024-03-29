<?php

use DPCalendar\View\BaseView;
use Joomla\CMS\Toolbar\ToolbarHelper;

/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2020 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */
defined('_JEXEC') or die();

class DPCalendarViewCoupons extends BaseView
{
	protected $items;
	protected $pagination;
	protected $state;

	protected function init()
	{
		$this->state      = $this->get('State');
		$this->items      = $this->get('Items');
		$this->pagination = $this->get('Pagination');
	}

	protected function addToolbar()
	{
		$state = $this->get('State');
		$canDo = DPCalendarHelper::getActions();

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
