<?php

/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2018 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */
defined('_JEXEC') or die();

if (empty($this->event->images->image_full)) {
	return;
}
?>
<div class="com-dpcalendar-event__image">
	<figure class="dp-figure">
		<img class="dp-image" src="<?php echo $this->event->images->image_full; ?>" alt="<?php echo $this->event->images->image_full_alt; ?>"
			 loading="lazy" <?php echo $this->event->images->image_full_dimensions; ?>>
		<?php if ($this->event->images->image_full_caption) { ?>
			<figcaption class="dp-figure__caption"><?php echo $this->event->images->image_full_caption; ?></figcaption>
		<?php } ?>
	</figure>
</div>
