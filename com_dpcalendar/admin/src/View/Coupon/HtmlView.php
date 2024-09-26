<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2020 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Component\DPCalendar\Administrator\View\Coupon;

\defined('_JEXEC') or die();

use DigitalPeak\Component\DPCalendar\Administrator\View\BaseView;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Helper\ContentHelper;
use Joomla\CMS\Toolbar\ToolbarHelper;

class HtmlView extends BaseView
{
	/** @var \stdClass */
	protected $coupon;

	/** @var Form */
	protected $form;

	protected function init(): void
	{
		$this->coupon = $this->get('Item');
		$this->form   = $this->get('Form');
	}

	protected function addToolbar(): void
	{
		$this->input->set('hidemainmenu', true);

		$isNew      = ($this->coupon->id == 0);
		$checkedOut = $this->coupon->checked_out != 0 && $this->coupon->checked_out != $this->user->id;
		$canDo      = ContentHelper::getActions('com_dpcalendar');

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
