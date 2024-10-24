<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2023 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

\defined('_JEXEC') or die();

use DigitalPeak\Component\DPCalendar\Administrator\HTML\Block\Icon;

$event = $this->displayData['event'];

if (empty($event->locations)) {
	return;
}
?>
<div class="dp-event__locations">
	<?php echo $this->layoutHelper->renderLayout(
		'block.icon',
		['icon' => Icon::LOCATION, 'title' => $this->translate('COM_DPCALENDAR_LOCATION')]
	); ?>
	<?php foreach ($event->locations as $index => $location) { ?>
		<div class="dp-location">
			<span class="dp-location__details"
				data-latitude="<?php echo $location->latitude; ?>"
				data-longitude="<?php echo $location->longitude; ?>"
				data-title="<?php echo $location->title; ?>"
				data-color="<?php echo $event->color; ?>"></span>
			<a href="<?php echo $this->router->getLocationRoute($location); ?>" class="dp-location__url dp-link">
				<span class="dp-location__title"><?php echo $location->title; ?></span>
				<?php if (!empty($event->roomTitles[$location->id])) { ?>
					<span class="dp-location__rooms">[<?php echo implode(', ', $event->roomTitles[$location->id]); ?>]</span>
				<?php } ?>
			</a>
			<?php if ($index < (is_countable($event->locations) ? count($event->locations) : 0) - 1) { ?>
				<span class="dp-location__separator">,</span>
			<?php } ?>
			<div class="dp-location__description">
				<?php echo $this->layoutHelper->renderLayout('event.tooltip', $this->displayData); ?>
			</div>
		</div>
	<?php } ?>
</div>
