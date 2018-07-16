<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2018 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */
defined('_JEXEC') or die();

class DPCalendarViewExtcalendar extends \DPCalendar\View\BaseView
{
	protected $state;
	protected $item;
	protected $form;

	public function init()
	{
		$this->state = $this->get('State');
		$this->item  = $this->get('Item');
		$this->form  = $this->get('Form');

		$this->form->removeField('alias');
		$this->form->removeField('ordering');
		$this->form->removeField('created');
		$this->form->removeField('created_by');
		$this->form->removeField('created_by_alias');
		$this->form->removeField('modified');
		$this->form->removeField('modified_by');
		$this->form->removeField('publish_up');
		$this->form->removeField('publish_down');
		$this->form->removeField('version');
		$this->form->removeField('asset_id');
		$this->form->removeField('sync_date');
		$this->form->removeField('sync_token');
	}

	protected function addToolbar()
	{
		JFactory::getApplication()->input->set('hidemainmenu', true);

		$user  = JFactory::getUser();
		$isNew = ($this->item->id == 0);
		$canDo = DPCalendarHelper::getActions();

		if ($canDo->get('core.edit')) {
			JToolbarHelper::apply('extcalendar.apply');
			JToolbarHelper::save('extcalendar.save');
		}
		if ($canDo->get('core.create')) {
			JToolbarHelper::save2new('extcalendar.save2new');
		}
		if (!$isNew && $canDo->get('core.create')) {
			JToolbarHelper::save2copy('extcalendar.save2copy');
		}
		if (empty($this->item->id)) {
			JToolbarHelper::cancel('extcalendar.cancel');
		} else {
			JToolbarHelper::cancel('extcalendar.cancel', 'JTOOLBAR_CLOSE');
		}

		JToolbarHelper::divider();
	}
}
