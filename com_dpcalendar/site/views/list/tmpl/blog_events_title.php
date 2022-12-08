<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2019 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */
defined('_JEXEC') or die();

$event = $this->displayData['event'];
?>
<h2 class="dp-event__title">
	<span class="dp-event__title-dot" style="background-color: #<?php echo $event->color; ?>"></span>
	<a href="<?php echo $this->router->getEventRoute($event->id, $event->catid); ?>" class="dp-event__link dp-link" target="_parent">
		<?php echo $event->title; ?>
	</a>
	<?php if ($event->state == 3) { ?>
		<span class="dp-event__title_canceled"><?php echo $this->translate('COM_DPCALENDAR_FIELD_VALUE_CANCELED'); ?></span>
	<?php } ?>
	<?php if ($event->state == 0) { ?>
		<span class="dp-event__title_unpublished"><?php echo $this->translate('JUNPUBLISHED'); ?></span>
	<?php } ?>
</h2>
