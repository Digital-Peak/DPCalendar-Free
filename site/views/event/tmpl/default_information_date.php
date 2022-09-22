<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2021 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */
defined('_JEXEC') or die();

if (!$this->params->get('event_show_date', '1')) {
	return;
}

$dateString = $this->dateHelper->getDateStringFromEvent(
	$this->event,
	$this->params->get('event_date_format', 'd.m.Y'),
	$this->params->get('event_time_format', 'H:i')
);
?>
<dl class="dp-description dp-information__date">
	<dt class="dp-description__label"><?php echo $this->translate('COM_DPCALENDAR_DATE'); ?></dt>
	<dd class="dp-description__description">
		<?php echo $dateString; ?>
		<?php if ($this->event->rrule) { ?>
			<div class="com-dpcalendar-event__rrule">
				<?php echo $this->dateHelper->transformRRuleToString($this->event->rrule, $this->event->start_date); ?>
			</div>
		<?php } ?>
	</dd>
</dl>
