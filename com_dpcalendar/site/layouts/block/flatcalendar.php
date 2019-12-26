<?php
/**
 * @package   DPCalendar
 * @author    Digital Peak http://www.digital-peak.com
 * @copyright Copyright (C) 2007 - 2019 Digital Peak. All rights reserved.
 * @license   http://www.gnu.org/licenses/gpl.html GNU/GPL
 */
defined('_JEXEC') or die();

$style = 'background-color: #' . $displayData['color'] . ';';
$style .= 'box-shadow: 0 2px 0 #' . $displayData['color'] . ';';
$style .= 'color: #' . \DPCalendar\Helper\DPCalendarHelper::getOppositeBWColor($displayData['color']) . ';';
?>
<div class="dp-flatcalendar">
	<span class="dp-flatcalendar__day"><?php echo $displayData['date']->format('j', true); ?></span>
	<span class="dp-flatcalendar__month" style="<?php echo $style; ?>">
		<?php echo $displayData['date']->format('M', true); ?>
	</span>
</div>
