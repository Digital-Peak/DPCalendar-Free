<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2024 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

\defined('_JEXEC') or die();

$url = 'index.php?option=com_dpcalendar&view=bookings' . ($this->input->getInt('e_id', 0) ? '&e_id=' . $this->input->getInt('e_id', 0) : '');

$this->params->set('form_state', 2);
$this->params->set('hidden_fields', $this->params->get('bookings_filter_form_hidden_fields', []));

$this->displayData['calendars'] = [];
$this->displayData['buttons']   = $this->afterButtonEventOutput;
$this->displayData['form']      = $this->filterForm;
$this->displayData['action']    = $this->router->route($url . $this->tmpl);

echo $this->layoutHelper->renderLayout('block.filter', $this->displayData);
