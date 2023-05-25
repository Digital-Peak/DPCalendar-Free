<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2020 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */
defined('_JEXEC') or die();

class DPCalendarViewCoupons extends \DPCalendar\View\BaseView
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

		if ($canDo->get('core.create')) {
			JToolbarHelper::addNew('coupon.add');
		}
		if ($canDo->get('core.edit')) {
			JToolbarHelper::editList('coupon.edit');
		}
		if ($canDo->get('core.edit.state')) {
			JToolbarHelper::publish('coupons.publish', 'JTOOLBAR_PUBLISH', true);
			JToolbarHelper::unpublish('coupons.unpublish', 'JTOOLBAR_UNPUBLISH', true);

			JToolbarHelper::archiveList('coupons.archive');
			JToolbarHelper::checkin('coupons.checkin');
		}
		if ($state->get('filter.state') == - 2 && $canDo->get('core.delete')) {
			JToolbarHelper::deleteList('', 'coupons.delete', 'JTOOLBAR_EMPTY_TRASH');
		} elseif ($canDo->get('core.edit.state')) {
			JToolbarHelper::trash('coupons.trash');
		}
		parent::addToolbar();
	}
}
