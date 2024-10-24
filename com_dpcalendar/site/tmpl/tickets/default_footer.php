<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2017 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

\defined('_JEXEC') or die();
?>
<div class="com-dpcalendar-tickets__footer dp-pagination dp-print-hide pagination">
	<div class="dp-pagination__counter"><?php echo $this->pagination->getPagesCounter(); ?></div>
	<div class="dp-pagination__links"><?php echo $this->pagination->getPagesLinks(); ?></div>
</div>
