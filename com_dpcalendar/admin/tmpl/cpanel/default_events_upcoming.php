<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2018 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

\defined('_JEXEC') or die();

use Joomla\CMS\Factory;
?>
<div class="com-dpcalendar-cpanel__new-events">
	<h3 class="dp-heading">
		<?php echo $this->translate('COM_DPCALENDAR_VIEW_CPANEL_UPCOMING_EVENTS'); ?>
	</h3>
	<div class="dp-information-container">
		<?php foreach ($this->upcomingEvents as $event) { ?>
			<div class="com-dpcalendar-cpanel__event">
				<a href="index.php?option=com_dpcalendar&view=event&e_id=<?php echo $event->id; ?>" class="dp-link">
					<?php echo $event->title; ?>
				</a>
				<div class="dp-information dp-grid">
					<span class="dp-information__label"><?php echo $this->translate('COM_DPCALENDAR_DATE'); ?>: </span>
					<span class="dp-information__content"><?php echo $this->dateHelper->getDateStringFromEvent($event); ?></span>
				</div>
				<div class="dp-information dp-grid">
					<span class="dp-information__label"><?php echo $this->translate('COM_DPCALENDAR_FIELD_CONFIG_EVENT_LABEL_AUTHOR'); ?>: </span>
					<span class="dp-information__content"><?php echo Factory::getUser($event->created_by)->name; ?></span>
				</div>
			</div>
		<?php } ?>
	</div>
</div>
