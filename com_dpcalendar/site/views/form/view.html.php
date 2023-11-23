<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2014 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

defined('_JEXEC') or die();

use DPCalendar\Helper\DPCalendarHelper;
use DPCalendar\View\BaseView;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Plugin\PluginHelper;

class DPCalendarViewForm extends BaseView
{
	public function init()
	{
		$user = $this->user;

		PluginHelper::importPlugin('dpcalendar');

		$this->app->getLanguage()->load('', JPATH_ADMINISTRATOR);

		$this->event      = $this->get('Item');
		$this->form       = $this->get('Form');
		$this->returnPage = $this->get('ReturnPage');

		$authorised = true;
		if (empty($this->event->id)) {
			$tmp        = $this->app->triggerEvent('onCalendarsFetch', [null, 'cd']);
			$authorised = DPCalendarHelper::canCreateEvent() || !empty(array_filter($tmp));
		}

		if (!$authorised && (is_countable($user->getAuthorisedCategories('com_dpcalendar', 'core.create')) ? count($user->getAuthorisedCategories('com_dpcalendar', 'core.create')) : 0) < 1) {
			return $this->handleNoAccess();
		}

		$this->seriesEvents = [];
		if ($this->event->original_id == -1) {
			$model = BaseDatabaseModel::getInstance('Adminevents', 'DPCalendarModel');
			$model->getState();
			$model->setState('filter.children', $this->event->id);
			$model->setState('filter.modified', $this->event->modified ?: '0000-00-00');
			$model->setState('filter.state', null);
			$model->setState('filter.search_start', null);

			foreach ($model->getItems() as $event) {
				$e                 = new stdClass();
				$e->title          = $event->title;
				$e->formatted_date = $this->dateHelper->getDateStringFromEvent(
					$event,
					$this->params->get('event_form_date_format', 'd.m.Y'),
					$this->params->get('event_form_time_format', 'H:i')
				);
				$e->edit_link         = $this->router->getEventRoute($event->id, $event->catid);
				$this->seriesEvents[] = $e;
			}
		}

		$requestParams = $this->input->get('jform', [], 'array');
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
			$this->form->removeField('exdates');
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

			if (in_array($fieldName, ['catid', 'all_day'])) {
				$this->form->setFieldAttribute($fieldName, 'type', 'hidden');
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
