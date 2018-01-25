<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2018 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */
defined('_JEXEC') or die();

class DPCalendarViewEvent extends \DPCalendar\View\LayoutView
{
	protected $layoutName = 'event.form.default';

	protected function init()
	{
		// Set the default model
		$this->setModel(JModelLegacy::getInstance('AdminEvent', 'DPCalendarModel'), true);

		$this->state = $this->get('State');
		$this->event = $this->get('Item');

		// Form stuff
		$this->form = $this->get('Form');
		$this->form->setFieldAttribute('user_id', 'type', 'hidden');
		$this->form->setFieldAttribute('start_date', 'format', DPCalendarHelper::getComponentParameter('event_form_date_format', 'm.d.Y'));
		$this->form->setFieldAttribute('start_date', 'formatTime', DPCalendarHelper::getComponentParameter('event_form_time_format', 'g:i a'));
		$this->form->setFieldAttribute('end_date', 'format', DPCalendarHelper::getComponentParameter('event_form_date_format', 'm.d.Y'));
		$this->form->setFieldAttribute('end_date', 'formatTime', DPCalendarHelper::getComponentParameter('event_form_time_format', 'g:i a'));
		$this->form->setFieldAttribute('scheduling_end_date', 'format', DPCalendarHelper::getComponentParameter('event_form_date_format', 'm.d.Y'));

		$this->canDo = DPCalendarHelper::getActions($this->state->get('filter.category_id'));

		$this->freeInformationText = '';
		if (DPCalendarHelper::isFree()) {
			$this->freeInformationText = '<br/><small class="text-warning" style="float:left">' .
				JText::_('COM_DPCALENDAR_ONLY_AVAILABLE_SUBSCRIBERS') .
				'</small>';
		}
	}

	protected function addToolbar()
	{
		$this->input->set('hidemainmenu', true);

		$isNew      = ($this->event->id == 0);
		$checkedOut = !($this->event->checked_out == 0 || $this->event->checked_out == $this->user->id);
		$canDo      = DPCalendarHelper::getActions($this->event->catid, 0);

		if (!$checkedOut && ($canDo->get('core.edit') || (count($this->user->getAuthorisedCategories('com_dpcalendar', 'core.create'))))) {
			JToolbarHelper::apply('event.apply');
			JToolbarHelper::save('event.save');
		}
		if (!$checkedOut && (count($this->user->getAuthorisedCategories('com_dpcalendar', 'core.create')))) {
			JToolbarHelper::save2new('event.save2new');
		}
		if (!$isNew && (count($this->user->getAuthorisedCategories('com_dpcalendar', 'core.create')) > 0)) {
			JToolbarHelper::save2copy('event.save2copy');
		}
		if ($this->state->params->get('save_history', 1) && $this->user->authorise('core.edit')) {
			JToolbarHelper::versions('com_dpcalendar.event', $this->event->id);
		}
		if (empty($this->event->id)) {
			JToolbarHelper::cancel('event.cancel');
		} else {
			JToolbarHelper::cancel('event.cancel', 'JTOOLBAR_CLOSE');
		}
		parent::addToolbar();
	}
}
