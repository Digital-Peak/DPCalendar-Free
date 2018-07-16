<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2018 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */
defined('_JEXEC') or die();
?>
<div class="dp-flatcalendar">
	<span class="dp-flatcalendar__day"><?php echo $displayData['date']->format('j', true); ?></span>
	<span class="dp-flatcalendar__month"
	      style="background-color: #<?php echo $displayData['color']; ?>; box-shadow: 0 2px 0 #<?php echo $displayData['color']; ?>">
		<?php echo $displayData['date']->format('M', true); ?>
	</span>
</div>
