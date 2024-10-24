<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2018 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

\defined('_JEXEC') or die();

$this->params->set('form_state', $this->params->get('calendar_filter_form', 1));
if ($this->activeFilters) {
	$this->params->set('form_state', 2);
}
$this->params->set('hidden_fields', $this->params->get('calendar_filter_form_hidden_fields', []));

$this->displayData['calendars'] = $this->visibleCalendars;
$this->displayData['form']      = $this->filterForm;

echo $this->layoutHelper->renderLayout('block.filter', $this->displayData);
