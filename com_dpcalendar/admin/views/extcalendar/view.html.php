<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2018 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */
defined('_JEXEC') or die();

class DPCalendarViewExtcalendar extends JViewLegacy
{

	protected $state;

	protected $item;

	protected $form;

	public function display ($tpl = null)
	{
		$this->state = $this->get('State');
		$this->item = $this->get('Item');
		$this->form = $this->get('Form');

		if (count($errors = $this->get('Errors')))
		{
			JError::raiseError(500, implode("\n", $errors));
			return false;
		}

		$this->addToolbar();
		parent::display($tpl);
	}

	protected function addToolbar ()
	{
		JFactory::getApplication()->input->set('hidemainmenu', true);

		$user = JFactory::getUser();
		$userId = $user->get('id');
		$isNew = ($this->item->id == 0);
		$canDo = DPCalendarHelper::getActions();

		if ($canDo->get('core.edit'))
		{
			JToolbarHelper::apply('extcalendar.apply');
			JToolbarHelper::save('extcalendar.save');
		}
		if ($canDo->get('core.create'))
		{
			JToolbarHelper::save2new('extcalendar.save2new');
		}
		if (! $isNew && $canDo->get('core.create'))
		{
			JToolbarHelper::save2copy('extcalendar.save2copy');
		}
		if (empty($this->item->id))
		{
			JToolbarHelper::cancel('extcalendar.cancel');
		}
		else
		{
			JToolbarHelper::cancel('extcalendar.cancel', 'JTOOLBAR_CLOSE');
		}

		JToolbarHelper::divider();
	}
}
