<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2018 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
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
