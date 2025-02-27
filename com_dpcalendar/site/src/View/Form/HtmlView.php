<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2014 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Component\DPCalendar\Site\View\Form;

\defined('_JEXEC') or die();

use DigitalPeak\Component\DPCalendar\Administrator\Helper\DPCalendarHelper;
use DigitalPeak\Component\DPCalendar\Administrator\View\BaseView;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Plugin\PluginHelper;

class HtmlView extends BaseView
{
	/** @var Form */
	protected $form;

	/** @var string */
	protected $returnPage;

	/** @var \stdClass */
	protected $event;

	/** @var array */
	protected $seriesEvents;

	protected function init(): void
	{
		$user = $this->user;

		PluginHelper::importPlugin('dpcalendar');

		$this->app->getLanguage()->load('', JPATH_ADMINISTRATOR);

		$this->event      = $this->getModel()->getItem() ?: new \stdClass();
		$this->form       = $this->getModel()->getForm();
		$this->returnPage = $this->getModel()->getReturnPage();

		$authorised = true;
		if (empty($this->event->id)) {
			$tmp        = $this->app->triggerEvent('onCalendarsFetch', [null, 'cd']);
			$authorised = DPCalendarHelper::canCreateEvent() || array_filter($tmp) !== [];
		}

		if (!$authorised && !$user->getAuthorisedCategories('com_dpcalendar', 'core.create')) {
			$this->handleNoAccess();
			return;
		}

		$this->seriesEvents = [];
		if ($this->event->original_id == -1) {
			$model = $this->getDPCalendar()->getMVCFactory()->createModel('Events', 'Administrator');
			$model->getState();
			$model->setState('filter.children', $this->event->id);
			$model->setState('filter.modified', $this->event->modified ?: '0000-00-00');
			$model->setState('filter.state', null);
			$model->setState('list.start-date', null);

			foreach ($model->getItems() as $event) {
				$e                 = new \stdClass();
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
		foreach ($requestParams as $key => $value) {
			if ($key === 'start_date') {
				$this->form->setFieldAttribute('start_date', 'filter', null);
				$this->form->setFieldAttribute('start_date', 'formatted', true);
				$this->form->setValue(
					'start_date',
					null,
					$requestParams['start_date'] . (\array_key_exists('start_date_time', $requestParams) ? ' ' . $requestParams['start_date_time'] : '')
				);
				continue;
			}

			if ($key === 'end_date') {
				$this->form->setFieldAttribute('end_date', 'filter', null);
				$this->form->setFieldAttribute('end_date', 'formatted', true);
				$this->form->setValue(
					'end_date',
					null,
					$requestParams['end_date'] . (\array_key_exists('end_date_time', $requestParams) ? ' ' . $requestParams['end_date_time'] : '')
				);
				continue;
			}

			if (!\is_array($value)) {
				$this->form->setValue($key, null, $value);
				continue;
			}

			$value = array_filter($value);

			// Its a value of a field group
			if ($this->form->getGroup($key)) {
				foreach ($value as $groupFieldKey => $groupFieldValue) {
					$this->form->setValue($groupFieldKey, $key, $groupFieldValue);
				}
				continue;
			}

			$this->form->setValue($key, null, $value);
		}

		$hiddenFields = $this->params->get('event_form_hidden_fields', []);

		if ($this->event->original_id > '0' || \in_array('scheduling', $hiddenFields)) {
			// Hide the scheduling fields
			$hiddenFields[] = 'rrule';
			$hiddenFields[] = 'exdates';
			$hiddenFields[] = 'scheduling';
			$hiddenFields[] = 'scheduling_end_date';
			$hiddenFields[] = 'scheduling_interval';
			$hiddenFields[] = 'scheduling_repeat_count';
			$hiddenFields[] = 'scheduling_daily_weekdays';
			$hiddenFields[] = 'scheduling_weekly_days';
			$hiddenFields[] = 'scheduling_monthly_options';
			$hiddenFields[] = 'scheduling_monthly_week';
			$hiddenFields[] = 'scheduling_monthly_week_days';
			$hiddenFields[] = 'scheduling_monthly_days';
		}

		if (!$this->params->get('save_history', 0)) {
			// Save is not activated
			$hiddenFields[] = 'version_note';
		}

		if ((!$this->event->id && !$user->authorise('core.edit.state', 'com_dpcalendar'))
			|| ($this->event->id && !$user->authorise('core.edit.state', 'com_dpcalendar.category.' . $this->event->catid))
		) {
			// Changing state is not allowed
			$hiddenFields[] = 'state';
		}

		foreach ($this->params->get('event_form_hidden_tabs', []) as $tabName) {
			if (empty($tabName)) {
				continue;
			}

			$parts = explode(':', (string)$tabName);
			$name  = $parts[0];
			$group = null;
			if (\count($parts) > 1) {
				$name  = $parts[1];
				$group = $parts[0];
			}

			if ($group == 'metadata') {
				$hiddenFields[] = 'xreference';
			}

			foreach ($this->form->getFieldset($name) as $field) {
				$hiddenFields[] = ($group !== null ? $group . ':' : '') . DPCalendarHelper::getFieldName($field);
			}
		}

		foreach ($hiddenFields as $fieldName) {
			if (empty($fieldName)) {
				continue;
			}

			if (\in_array($fieldName, ['catid', 'all_day'])) {
				$this->form->setFieldAttribute($fieldName, 'type', 'hidden');
				continue;
			}

			$parts = explode(':', (string)$fieldName);
			if (\count($parts) > 1) {
				$hiddenFields[] = $this->form->removeField($parts[1], $parts[0]);
			} else {
				$hiddenFields[] = $this->form->removeField($parts[0]);
			}
		}

		parent::init();
	}
}
