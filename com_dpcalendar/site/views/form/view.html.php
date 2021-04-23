<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2014 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */
defined('_JEXEC') or die();

class DPCalendarViewForm extends \DPCalendar\View\BaseView
{
	public function init()
	{
		$user = $this->user;

		JPluginHelper::importPlugin('dpcalendar');

		$this->app->getLanguage()->load('', JPATH_ADMINISTRATOR);

		$this->event      = $this->get('Item');
		$this->form       = $this->get('Form');
		$this->returnPage = $this->get('ReturnPage');

		$authorised = true;
		if (empty($this->event->id)) {
			$tmp        = $this->app->triggerEvent('onCalendarsFetch', [null, 'cd']);
			$authorised = DPCalendarHelper::canCreateEvent() || !empty(array_filter($tmp));
		}

		if (!$authorised && count($user->getAuthorisedCategories('com_dpcalendar', 'core.create')) < 1) {
			return $this->handleNoAccess();
		}

		$requestParams = $this->input->getVar('jform', []);
		if (key_exists('start_date', $requestParams)) {
			$this->form->setFieldAttribute('start_date', 'filter', null);
			$this->form->setFieldAttribute('start_date', 'formated', true);
			$this->form->setValue(
				'start_date',
				null,
				$requestParams['start_date'] . (key_exists('start_date_time', $requestParams) ? ' ' . $requestParams['start_date_time'] : '')
			);
		}

		if (key_exists('end_date', $requestParams)) {
			$this->form->setFieldAttribute('end_date', 'filter', null);
			$this->form->setFieldAttribute('end_date', 'formated', true);
			$this->form->setValue(
				'end_date',
				null,
				$requestParams['end_date'] . (key_exists('end_date_time', $requestParams) ? ' ' . $requestParams['end_date_time'] : '')
			);
		}

		if (key_exists('title', $requestParams)) {
			$this->form->setValue('title', null, $requestParams['title']);
		}

		if (key_exists('catid', $requestParams)) {
			$this->form->setValue('catid', null, $requestParams['catid']);
		}

		if (key_exists('location_ids', $requestParams) && $requestParams['location_ids'] && reset($requestParams['location_ids'])) {
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

		if (!$this->params->get('save_history', 0)) {
			// Save is not activated
			$this->form->removeField('version_note');
		}

		if ((!$this->event->id && !$user->authorise('core.edit.state', 'com_dpcalendar'))
			|| ($this->event->id && !$user->authorise('core.edit.state', 'com_dpcalendar.category.' . $this->event->catid))
		) {
			// Changing state is not allowed
			$this->form->removeField('state');
		}

		foreach ($this->params->get('event_form_hidden_tabs', []) as $tabName) {
			if (empty($tabName)) {
				continue;
			}

			$parts = explode(':', $tabName);
			$name  = $parts[0];
			$group = null;
			if (count($parts) > 1) {
				$name  = $parts[1];
				$group = $parts[0];
			}

			if ($group == 'metadata') {
				$this->form->removeField('xreference');
			}

			foreach ($this->form->getFieldset($name) as $field) {
				$this->form->removeField(DPCalendarHelper::getFieldName($field), $group);
			}
		}

		foreach ($this->params->get('event_form_hidden_fields', []) as $fieldName) {
			if (empty($fieldName)) {
				continue;
			}

			$parts = explode(':', $fieldName);
			if (count($parts) > 1) {
				$this->form->removeField($parts[1], $parts[0]);
			} else {
				$this->form->removeField($parts[0]);
			}
		}

		return parent::init();
	}
}
