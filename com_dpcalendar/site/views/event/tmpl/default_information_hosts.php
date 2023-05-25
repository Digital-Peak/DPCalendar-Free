<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2022 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

defined('_JEXEC') or die();

if (!$this->event->hosts || !$this->params->get('event_show_hosts', '1')) {
	return;
}
?>
<dl class="dp-description dp-information__hosts">
	<dt class="dp-description__label"><?php echo $this->translate('COM_DPCALENDAR_VIEW_EVENT_HOSTS_LABEL'); ?></dt>
	<dd class="dp-description__description">
		<?php foreach ($this->event->hosts as $index => $host) { ?>
			<?php if (!empty($host->link)) { ?>
				<a href="<?php echo $host->link; ?>" class="dp-link"><?php echo $host->name; ?></a>
			<?php } else { ?>
				<?php echo $host->name; ?>
			<?php } ?>
			<?php if ($index < count($this->event->hosts) - 1) { ?>
				|
			<?php } ?>
		<?php } ?>
	</dd>
</dl>
