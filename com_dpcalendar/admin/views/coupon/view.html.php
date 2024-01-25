<?php

use DPCalendar\View\BaseView;
use Joomla\CMS\Toolbar\ToolbarHelper;

/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2020 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */
defined('_JEXEC') or die();

class DPCalendarViewCoupon extends BaseView
{
	public $coupon;
	public $form;
	protected function init()
	{
		$this->coupon = $this->get('Item');
		$this->form   = $this->get('Form');
	}

	protected function addToolbar()
	{
		$this->input->set('hidemainmenu', true);

		$isNew      = ($this->coupon->id == 0);
		$checkedOut = $this->coupon->checked_out != 0 && $this->coupon->checked_out != $this->user->id;
		$canDo      = DPCalendarHelper::getActions();

		if (!$checkedOut && $canDo->get('core.edit')) {
			ToolbarHelper::apply('coupon.apply');
			ToolbarHelper::save('coupon.save');
		}
		if (!$checkedOut && $canDo->get('core.create')) {
			ToolbarHelper::save2new('coupon.save2new');
		}
		if (!$isNew && $canDo->get('core.create')) {
			ToolbarHelper::save2copy('coupon.save2copy');
		}
		if (empty($this->coupon->id)) {
			ToolbarHelper::cancel('coupon.cancel');
		} else {
			ToolbarHelper::cancel('coupon.cancel', 'JTOOLBAR_CLOSE');
		}

		ToolbarHelper::divider();
		parent::addToolbar();
	}
}
