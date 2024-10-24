<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2021 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

\defined('_JEXEC') or die();

if (!$this->authorName || !$this->params->get('event_show_author', '1')) {
	return;
}
?>
<dl class="dp-description dp-information__author">
	<dt class="dp-description__label"><?php echo $this->translate('COM_DPCALENDAR_FIELD_CONFIG_EVENT_LABEL_AUTHOR'); ?></dt>
	<dd class="dp-description__description">
		<?php if ($this->event->contact_link) { ?>
			<a href="<?php echo $this->event->contact_link; ?>" class="dp-link">
				<?php echo $this->authorName . $this->avatar; ?>
			</a>
		<?php } else { ?>
			<?php echo $this->authorName . $this->avatar; ?>
		<?php } ?>
	</dd>
</dl>
