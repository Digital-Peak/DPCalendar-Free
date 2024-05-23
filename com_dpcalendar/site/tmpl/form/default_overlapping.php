<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2018 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */
defined('_JEXEC') or die();

if (!$this->params->get('event_form_check_overlaping', 0)) {
	return;
}
?>
<div class="com-dpcalendar-eventform__overlapping dp-info-box"
	 data-overlapping="<?php echo $this->params->get('event_form_check_overlaping', 0) == '2'; ?>"></div>
