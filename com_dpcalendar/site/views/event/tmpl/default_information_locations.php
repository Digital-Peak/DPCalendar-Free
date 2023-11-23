<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2021 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

defined('_JEXEC') or die();

if (!$this->event->locations || !$this->params->get('event_show_location', '2')) {
	return;
}
?>
<dl class="dp-description dp-information__locations">
	<dt class="dp-description__label">
		<?php echo $this->translate('COM_DPCALENDAR_LOCATION' . ((is_countable($this->event->locations) ? count($this->event->locations) : 0) > 1 ? 'S' : '')); ?>
	</dt>
	<dd class="dp-description__description dp-locations">
		<?php foreach ($this->event->locations as $index => $location) { ?>
			<span class="dp-location">
				<?php $url = $this->params->get('event_show_location', '2') == '1' ?
					$this->router->getLocationRoute($location) : '#dp-location-' . $location->id; ?>
				<a href="<?php echo $url; ?>" class="dp-link dp-location__url">
					<span class="dp-location__title"><?php echo $location->title; ?></span>
					<?php if (!empty($this->event->roomTitles[$location->id])) { ?>
						<span class="dp-location__rooms">[<?php echo implode(', ', $this->event->roomTitles[$location->id]); ?>]</span>
					<?php } ?>
				</a>
				<?php if ($index < (is_countable($this->event->locations) ? count($this->event->locations) : 0) - 1) { ?>
					<span class="dp-location__separator">,</span>
				<?php } ?>
			</span>
		<?php } ?>
	</dd>
</dl>
