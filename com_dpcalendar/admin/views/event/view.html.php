<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2018 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */
defined('_JEXEC') or die();

class DPCalendarViewEvent extends \DPCalendar\View\BaseView
{
	public function init()
	{
		// Set the default model
		$this->setModel(JModelLegacy::getInstance('AdminEvent', 'DPCalendarModel'), true);

		$this->event      = $this->get('Item');
		$this->form       = $this->get('Form');
		$this->returnPage = $this->get('ReturnPage');

		JForm::addFieldPath(JPATH_COMPONENT_ADMINISTRATOR . '/models/files/');
		JModelLegacy::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_dpcalendar/models', 'DPCalendarModel');
		$this->locationForm = JModelLegacy::getInstance('Location', 'DPCalendarModel', ['ignore_request' => true])->getForm([], false, 'location');
		$this->locationForm->setFieldAttribute('title', 'required', false);
		$this->locationForm->setFieldAttribute('rooms', 'label', 'COM_DPCALENDAR_ROOMS');

		$this->form->setFieldAttribute('user_id', 'type', 'hidden');
		$this->form->setFieldAttribute('start_date', 'format', DPCalendarHelper::getComponentParameter('event_form_date_format', 'm.d.Y'));
		$this->form->setFieldAttribute('start_date', 'formatTime', DPCalendarHelper::getComponentParameter('event_form_time_format', 'g:i a'));
		$this->form->setFieldAttribute('end_date', 'format', DPCalendarHelper::getComponentParameter('event_form_date_format', 'm.d.Y'));
		$this->form->setFieldAttribute('end_date', 'formatTime', DPCalendarHelper::getComponentParameter('event_form_time_format', 'g:i a'));
		$this->form->setFieldAttribute('scheduling_end_date', 'format', DPCalendarHelper::getComponentParameter('event_form_date_format', 'm.d.Y'));


		if ($this->event->original_id > '0') {
			// Hide the scheduling fields
			$this->form->removeField('rrule');
			$this->form->removeField('scheduling');
			$this->form->removeField('scheduling_end_date');
			$this->form->removeField('scheduling_interval');
			$this->form->removeField('scheduling_repeat_count');
			$this->form->removeField('scheduling_daily_weekdays');
			$this->form->removeField('scheduling_weekly_days');
			$this->form->removeField('scheduling_monthly_options');
			$this->form->removeField('scheduling_monthly_week');
			$this->form->removeField('scheduling_monthly_week_days');
			$this->form->removeField('scheduling_monthly_days');
		}
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
