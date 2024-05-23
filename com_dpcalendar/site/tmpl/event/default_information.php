<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2015 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

defined('_JEXEC') or die();
?>
<div class="com-dpcalendar-event__information dp-information">
	<?php echo $this->loadTemplate('information_calendar'); ?>
	<?php echo $this->loadTemplate('information_date'); ?>
	<?php echo $this->loadTemplate('information_locations'); ?>
	<?php echo $this->loadTemplate('information_author'); ?>
	<?php echo $this->loadTemplate('information_hosts'); ?>
	<?php echo $this->loadTemplate('information_url'); ?>
	<div class="com-dpcalendar-event__event-text">
		<?php echo $this->event->displayEvent->beforeDisplayContent; ?>
	</div>
	<?php echo $this->loadTemplate('tags'); ?>
</div>
