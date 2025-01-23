<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2024 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

\defined('_JEXEC') or die();

$url = 'index.php?option=com_dpcalendar&view=tickets';
foreach (['Itemid', 'e_id', 'b_id'] as $key) {
	if ($value = $this->input->getInt($key, 0)) {
		$url .= '&' . $key . '=' . $value;
	}
}

$this->params->set('form_state', 2);
$this->params->set('hidden_fields', $this->params->get('tickets_filter_form_hidden_fields', []));

$this->displayData['calendars'] = [];
$this->displayData['buttons']   = $this->afterButtonEventOutput;
$this->displayData['form']      = $this->filterForm;
$this->displayData['action']    = $this->router->route($url . $this->tmpl);

echo $this->layoutHelper->renderLayout('block.filter', $this->displayData);
