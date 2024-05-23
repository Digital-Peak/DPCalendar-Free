<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2021 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */
defined('_JEXEC') or die();

if (!$this->params->get('show_page_heading')) {
	return;
}
?>
<div class="com-dpcalendar-calendar-timeline__heading">
	<h1 class="dp-page-heading page-header"><?php echo $this->escape($this->params->get('page_heading')); ?></h1>
</div>
