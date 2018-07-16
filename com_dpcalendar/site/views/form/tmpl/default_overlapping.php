<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2018 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */
defined('_JEXEC') or die();

if (!$this->params->get('event_form_check_overlaping', 0)) {
	return;
}
?>
<div class="com-dpcalendar-eventform__overlapping dp-info-box"
	 data-overlapping="<?php echo $this->params->get('event_form_check_overlaping', 0) == '2'; ?>"></div>
