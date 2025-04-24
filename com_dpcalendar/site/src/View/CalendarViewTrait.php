<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2014 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Component\DPCalendar\Site\View;

\defined('_JEXEC') or die();

use DigitalPeak\Component\DPCalendar\Administrator\Calendar\Calendar;
use DigitalPeak\Component\DPCalendar\Administrator\Calendar\CalendarInterface;
use DigitalPeak\Component\DPCalendar\Site\Model\EventsModel;
use Joomla\CMS\Form\Form;
use Joomla\Utilities\ArrayHelper;

trait CalendarViewTrait
{
	/**
	 * Does initialize the filter form and active filters.
	 */
	private function prepareForm(array $calendars): void
	{
		// Ensure the correct name
		$name = strtolower($this->getName());

		// Load the model for the form and active filters
		$model = $this->getModel();
		if (!$model instanceof EventsModel) {
			$model = $this->getDPCalendar()->getMVCFactory()->createModel(
				'Events',
				'Site',
				['name' => $name . '.' . $this->app->getInput()->getInt('Itemid', 0)]
			);
			$model->getState();
		}

		// Ensure filters are set
		$this->activeFilters = $this->activeFilters ?: $model->getActiveFilters();

		// Remove calendars when equal
		if (\array_key_exists('calendars', $this->activeFilters)
			&& \count(array_filter((array)$this->activeFilters['calendars'], fn ($c): bool => $c != '-2' && !empty($c))) === \count($calendars)) {
			unset($this->activeFilters['calendars']);
		}

		// Remove radius when default
		if (\array_key_exists('radius', $this->activeFilters) && $this->activeFilters['radius'] == $this->params->get($name . '_filter_radius', 20)) {
			unset($this->activeFilters['radius']);
		}

		// Remove length type when default
		if (\array_key_exists('length-type', $this->activeFilters) && $this->activeFilters['length-type'] == $this->params->get($name . '_filter_length_type', 'm')) {
			unset($this->activeFilters['length-type']);
		}

		// Load the filter form
		Form::addFormPath(JPATH_ADMINISTRATOR . '/components/com_dpcalendar/forms');

		// Load the form
		$this->filterForm = $model->getFilterForm();

		// Set the passed calendars as default value to the form
		if (empty($this->filterForm->getValue('calendars', 'filter'))) {
			$this->filterForm->setValue('calendars', 'filter', array_keys($calendars));
		}

		// Remove not needed fields
		$this->filterForm->removeField('calendars', 'filter');
		$this->filterForm->removeField('event_type', 'filter');
		$this->filterForm->removeField('access', 'filter');
		$this->filterForm->removeField('language', 'filter');
		$this->filterForm->removeField('level', 'filter');
		$this->filterForm->removeField('fullordering', 'list');
		$this->filterForm->removeField('limit', 'list');

		if (!$this->getCurrentUser()->authorise('core.edit.state', 'com_dpcalendar')) {
			$this->filterForm->removeField('state', 'filter');
		}

		// Enable autocomplete when configured
		$this->filterForm->setFieldAttribute('location', 'data-dp-autocomplete', $this->params->get($name . '_filter_form_location_autocomplete', 1), 'filter');

		$this->filterForm->setFieldAttribute('calendars', 'ids', implode(',', (array)$this->params->get('ids', ['-1'])), 'filter');

		$hiddenFields = $this->params->get($name . '_filter_form_hidden_fields', []);

		// Check if the menu item contains some predefined values, then it will be hidden in the form
		foreach (['author' => 'created_by', 'tags' => 'tags', 'locations' => 'location'] as $param => $field) {
			if ($this->params->get($name . '_filter_' . $param, 0)) {
				$hiddenFields[] = $field;
			}
		}

		// Remove the not needed fields
		foreach ($hiddenFields as $field) {
			$this->filterForm->removeField($field, 'filter');
			$this->filterForm->removeField($field, 'list');
			$this->filterForm->removeField($field, 'com_fields');

			if ($field === 'location') {
				$this->filterForm->removeField('radius', 'filter');
				$this->filterForm->removeField('length-type', 'filter');
			}

			if (\array_key_exists($field, $this->activeFilters)) {
				unset($this->activeFilters[$field]);
			}
		}

		if (!$this->filterForm->getValue('radius', 'filter')) {
			$this->filterForm->setValue('radius', 'filter', $this->params->get($name . '_filter_radius', 20));
		}

		if (!$this->filterForm->getValue('length-type', 'filter')) {
			$this->filterForm->setValue('length-type', 'filter', $this->params->get($name . '_filter_length_type', 'm'));
		}

		// Set the date formats
		$this->filterForm->setFieldAttribute('start-date', 'format', $this->params->get('event_form_date_format', 'd.m.Y'), 'list');
		$this->filterForm->setFieldAttribute('end-date', 'format', $this->params->get('event_form_date_format', 'd.m.Y'), 'list');

		// Make the active filters flat for easy access
		$this->activeFilters = ArrayHelper::flatten($this->activeFilters);
	}

	/**
	 * Does setup the given calendars from the content events.
	 */
	private function fillCalendar(CalendarInterface $calendar): void
	{
		if ((property_exists($calendar, 'event') && $calendar->event !== null) || !$calendar instanceof Calendar) {
			return;
		}

		// @phpstan-ignore-next-line
		$calendar->event = new \stdClass();

		// For some plugins
		$calendar->text = $calendar->getDescription();

		$this->app->triggerEvent('onContentPrepare', ['com_dpcalendar.categories', $calendar, $calendar->getParams(), 0]);

		$results = $this->app->triggerEvent(
			'onContentAfterTitle',
			['com_dpcalendar.categories', &$calendar, &$this->params, 0]
		);
		$calendar->event->afterDisplayTitle = trim(implode("\n", $results));

		$results = $this->app->triggerEvent(
			'onContentBeforeDisplay',
			['com_dpcalendar.categories', &$calendar, &$this->params, 0]
		);
		$calendar->event->beforeDisplayContent = trim(implode("\n", $results));

		$results = $this->app->triggerEvent(
			'onContentAfterDisplay',
			['com_dpcalendar.categories', &$calendar, &$this->params, 0]
		);
		$calendar->event->afterDisplayContent = trim(implode("\n", $results));

		if ($calendar->text === '' || $calendar->text === '0') {
			return;
		}

		$calendar->setDescription($calendar->text);

		foreach ($calendar->getChildren(true) as $child) {
			$this->fillCalendar($child);
		}
	}
}
