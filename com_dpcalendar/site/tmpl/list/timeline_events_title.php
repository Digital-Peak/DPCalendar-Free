<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2019 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

\defined('_JEXEC') or die();

use DigitalPeak\Component\DPCalendar\Administrator\Helper\DPCalendarHelper;

$event = $this->displayData['event'];
?>
<h2 class="dp-event__title" style="background-color: #<?php echo $event->color; ?>;">
	<a href="<?php echo $this->router->getEventRoute($event->id, $event->catid); ?>" class="dp-event__link dp-link"
	   style="color: #<?php echo DPCalendarHelper::getOppositeBWColor($event->color); ?>">
		<?php echo $event->title; ?>
	</a>
	<?php if ($event->state == 0) { ?>
		<span class="dp-event__title_unpublished"><?php echo $this->translate('JUNPUBLISHED'); ?></span>
	<?php } ?>
	<?php if ($event->state == 2) { ?>
		<span class="dp-event__title_archived"><?php echo $this->translate('JARCHIVED'); ?></span>
	<?php } ?>
	<?php if ($event->state == 3) { ?>
		<span class="dp-event__title_canceled"><?php echo $this->translate('COM_DPCALENDAR_FIELD_VALUE_CANCELED'); ?></span>
	<?php } ?>
	<?php if ($event->state == 4) { ?>
		<span class="dp-event__title_reported"><?php echo $this->translate('COM_DPCALENDAR_FIELD_VALUE_REPORTED'); ?></span>
	<?php } ?>
	<?php if ($event->state == -2) { ?>
		<span class="dp-event__title_trashed"><?php echo $this->translate('JTRASHED'); ?></span>
	<?php } ?>
</h2>
