<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2018 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */
defined('_JEXEC') or die();

class DPCalendarViewLocation extends \DPCalendar\View\LayoutView
{
	protected $layoutName = 'location.form.default';

	public function init()
	{
		$this->location = $this->get('Item');
		$this->form     = $this->get('Form');
	}

	protected function addToolbar()
	{
		$this->input->set('hidemainmenu', true);

		$isNew      = ($this->location->id == 0);
		$checkedOut = !($this->location->checked_out == 0 || $this->location->checked_out == $this->user->id);
		$canDo      = DPCalendarHelper::getActions();

		if (!$checkedOut && $canDo->get('core.edit')) {
			JToolbarHelper::apply('location.apply');
			JToolbarHelper::save('location.save');
		}
		if (!$checkedOut && $canDo->get('core.create')) {
			JToolbarHelper::save2new('location.save2new');
		}
		if (!$isNew && $canDo->get('core.create')) {
			JToolbarHelper::save2copy('location.save2copy');
		}
		if (empty($this->location->id)) {
			JToolbarHelper::cancel('location.cancel');
		} else {
			JToolbarHelper::cancel('location.cancel', 'JTOOLBAR_CLOSE');
		}

		JToolbarHelper::divider();
		parent::addToolbar();
	}
}
