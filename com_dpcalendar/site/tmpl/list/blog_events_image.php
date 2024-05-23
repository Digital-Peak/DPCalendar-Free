<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2019 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */
defined('_JEXEC') or die();

$event = $this->displayData['event'];
if (!$event->images->image_intro) {
	return;
}
?>
<div class="dp-event__image">
	<figure class="dp-figure">
		<a href="<?php echo $this->router->getEventRoute($event->id, $event->catid); ?>" class="dp-link">
			<img class="dp-image" src="<?php echo $event->images->image_intro; ?>" alt="<?php echo $event->images->image_intro_alt; ?>"
				 loading="lazy" <?php echo $event->images->image_intro_dimensions; ?>>
		</a>
		<?php if ($event->images->image_intro_caption) { ?>
			<figcaption class="dp-figure__caption"><?php echo $event->images->image_intro_caption; ?></figcaption>
		<?php } ?>
	</figure>
</div>
