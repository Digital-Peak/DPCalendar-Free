<?php

use DPCalendar\View\BaseView;
use Joomla\CMS\Toolbar\ToolbarHelper;

/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2014 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */
defined('_JEXEC') or die();

class DPCalendarViewLocation extends BaseView
{
	public $location;
	public $form;
	protected function init()
	{
		$this->location = $this->get('Item');
		$this->form     = $this->get('Form');
	}

	protected function addToolbar()
	{
		$this->input->set('hidemainmenu', true);

		$isNew      = ($this->location->id == 0);
		$checkedOut = $this->location->checked_out != 0 && $this->location->checked_out != $this->user->id;
		$canDo      = DPCalendarHelper::getActions();

		if (!$checkedOut && $canDo->get('core.edit')) {
			ToolbarHelper::apply('location.apply');
			ToolbarHelper::save('location.save');
		}
		if (!$checkedOut && $canDo->get('core.create')) {
			ToolbarHelper::save2new('location.save2new');
		}
		if (!$isNew && $canDo->get('core.create')) {
			ToolbarHelper::save2copy('location.save2copy');
		}
		if (empty($this->location->id)) {
			ToolbarHelper::cancel('location.cancel');
		} else {
			ToolbarHelper::cancel('location.cancel', 'JTOOLBAR_CLOSE');
		}

		ToolbarHelper::divider();
		parent::addToolbar();
	}
}
