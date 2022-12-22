<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2018 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

defined('_JEXEC') or die();
?>
<div class="com-dpcalendar-calendar__calendar dp-calendar"
	data-options="DPCalendar.view.calendar.<?php echo $this->input->getInt('Itemid', 0); ?>.options"></div>
