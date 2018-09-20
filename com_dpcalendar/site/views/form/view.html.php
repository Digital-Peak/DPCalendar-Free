<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2018 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */
defined('_JEXEC') or die();

class DPCalendarViewForm extends \DPCalendar\View\BaseView
{
	public function init()
	{
		$user = $this->user;
		if ($user->guest && count($user->getAuthorisedCategories('com_dpcalendar', 'core.create')) < 1) {
			$active = $this->app->getMenu()->getActive();
			$link   = new JUri(JRoute::_('index.php?option=com_users&view=login&Itemid=' . $active->id, false));
			$link->setVar('return', base64_encode('index.php?Itemid=' . $active->id));

			$this->app->redirect(JRoute::_($link), JText::_('COM_DPCALENDAR_NOT_LOGGED_IN'), 'warning');

			return false;
		}

		JPluginHelper::importPlugin('dpcalendar');

		$this->app->getLanguage()->load('', JPATH_ADMINISTRATOR);

		$this->event      = $this->get('Item');
		$this->form       = $this->get('Form');
		$this->returnPage = $this->get('ReturnPage');

		JForm::addFieldPath(JPATH_COMPONENT_ADMINISTRATOR . '/models/files/');
		JModelLegacy::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_dpcalendar/models', 'DPCalendarModel');
		$this->locationForm = JModelLegacy::getInstance('Location', 'DPCalendarModel', ['ignore_request' => true])->getForm([], false, 'location');
		$this->locationForm->setFieldAttribute('title', 'required', false);
		$this->locationForm->setFieldAttribute('rooms', 'label', 'COM_DPCALENDAR_ROOMS');

		$authorised = true;
		if (empty($this->event->id)) {
			$tmp        = $this->app->triggerEvent('onCalendarsFetch', array(null, 'cd'));
			$authorised = DPCalendarHelper::canCreateEvent() || !empty(array_filter($tmp));
		}

		if ($authorised !== true) {
			throw new Exception(JText::_('JERROR_ALERTNOAUTHOR'), 403);
		}

		$requestParams = $this->input->getVar('jform', array());
		if (key_exists('start_date', $requestParams)) {
			$this->form->setFieldAttribute('start_date', 'filter', null);
			$this->form->setFieldAttribute('start_date', 'formated', true);
			$this->form->setValue('start_date', null,
				$requestParams['start_date'] . (key_exists('start_date_time', $requestParams) ? ' ' . $requestParams['start_date_time'] : ''));
		}

		if (key_exists('end_date', $requestParams)) {
			$this->form->setFieldAttribute('end_date', 'filter', null);
			$this->form->setFieldAttribute('end_date', 'formated', true);
			$this->form->setValue('end_date', null,
				$requestParams['end_date'] . (key_exists('end_date_time', $requestParams) ? ' ' . $requestParams['end_date_time'] : ''));
		}

		if (key_exists('title', $requestParams)) {
			$this->form->setValue('title', null, $requestParams['title']);
		}

		if (key_exists('catid', $requestParams)) {
			$this->form->setValue('catid', null, $requestParams['catid']);
		}

		if (key_exists('location_ids', $requestParams)) {
			$this->form->setValue('location_ids', null, $requestParams['location_ids']);
		}

		if (key_exists('rooms', $requestParams)) {
			$this->form->setValue('rooms', null, $requestParams['rooms']);
		}

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

		$hideFieldsets = [];
		if (!$this->params->get('event_form_change_location', 1)) {
			$hideFieldsets[] = 'location';
		}
		if (!$this->params->get('event_form_change_options', 1)) {
			$hideFieldsets['params'] = 'jbasic';
		}
		if (!$this->params->get('event_form_change_book', 1)) {
			$hideFieldsets[] = 'booking';
		}
		if (!$this->params->get('event_form_change_publishing', 1)) {
			$hideFieldsets[] = 'publishing';
		}
		if (!$this->params->get('event_form_change_metadata', 1)) {
			$hideFieldsets[] = 'jmetadata';
			$hideFieldsets['metadata'] = 'jmetadata';
		}

		foreach ($hideFieldsets as $group => $name) {
			foreach ($this->form->getFieldset($name) as $field) {
				if (!is_string($group)) {
					$group = null;
				}
				$this->form->removeField($field->fieldname, $group);
			}
		}

		if (!$this->params->get('save_history', 0)) {
			// Save is not activated
			$this->form->removeField('version_note');
		}

		if ($this->params->get('event_form_change_tags', '1') != '1') {
			// Tags can't be changed
			$this->form->removeField('tags');
		}

		if ((!$this->event->id && !$user->authorise('core.edit.state', 'com_dpcalendar'))
			|| ($this->event->id && !$user->authorise('core.edit.state', 'com_dpcalendar.category.' . $this->event->catid))
		) {
			// Changing state is not allowed
			$this->form->removeField('state');
		}

		// Remove fields depending on the params
		if ($this->params->get('event_form_change_calid', '1') != '1') {
			$this->form->setFieldAttribute('catid', 'readonly', 'readonly');
		}
		if ($this->params->get('event_form_change_show_end_time', '1') != '1') {
			$this->form->removeField('show_end_time');
		}
		if ($this->params->get('event_form_change_color', '1') != '1') {
			$this->form->removeField('color');
		}
		if ($this->params->get('event_form_change_url', '1') != '1') {
			$this->form->removeField('url');
		}
		if ($this->params->get('event_form_change_images', '1') != '1') {
			$this->form->removeGroup('images');
		}
		if ($this->params->get('event_form_change_description', '1') != '1') {
			$this->form->removeField('description');
		}
		if ($this->params->get('event_form_change_capacity', '1') != '1') {
			$this->form->removeField('capacity');
		}
		if ($this->params->get('event_form_change_capacity_used', '1') != '1') {
			$this->form->removeField('capacity_used');
		}
		if ($this->params->get('event_form_change_max_tickets', '1') != '1') {
			$this->form->removeField('max_tickets');
		}
		if ($this->params->get('event_form_change_price', '1') != '1') {
			$this->form->removeField('price');
		}
		if ($this->params->get('event_form_change_payment', '1') != '1') {
			$this->form->removeField('plugintype');
		}
		if ($this->params->get('event_form_change_access', '1') != '1') {
			$this->form->removeField('access');
		}
		if ($this->params->get('event_form_change_access_content', '1') != '1') {
			$this->form->removeField('access_content');
		}
		if ($this->params->get('event_form_change_featured', '1') != '1') {
			$this->form->removeField('featured');
		}

		// Handle tabs
		if ($this->params->get('event_form_change_location', '1') != '1') {
			$this->form->removeField('location');
			$this->form->removeField('location_ids');
		}

		return parent::init();
	}
}
