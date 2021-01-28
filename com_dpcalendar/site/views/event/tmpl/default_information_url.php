<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2021 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */
defined('_JEXEC') or die();

if (!$this->event->url || !$this->params->get('event_show_url', '1')) {
	return;
}
?>
<dl class="dp-description">
	<dt class="dp-description__label"><?php echo $this->translate('COM_DPCALENDAR_FIELD_CONFIG_EVENT_LABEL_URL'); ?></dt>
	<dd class="dp-description__description">
		<?php $u = JUri::getInstance($this->event->url); ?>
		<a href="<?php echo $this->event->url; ?>" class="dp-link"
		   target="<?php echo $u->getHost() && JUri::getInstance()->getHost() != $u->getHost() ? '_blank' : ''; ?>">
			<?php echo $this->event->url; ?>
		</a>
	</dd>
</dl>
