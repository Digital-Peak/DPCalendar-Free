<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2018 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */
defined('_JEXEC') or die();

if (!$this->params->get('show_page_heading')) {
	return;
}
?>
<div class="com-dpcalendar-invite__heading">
	<h1 class="dp-page-heading"><?php echo $this->escape($this->params->get('page_heading')); ?></h1>
</div>
