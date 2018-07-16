<?php

/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2018 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */
defined('_JEXEC') or die();

if (empty($this->event->images->image_full)) {
	return;
}
?>
<div class="com-dpcalendar-event__image">
	<figure class="dp-figure">
		<img class="dp-image" src="<?php echo $this->event->images->image_full; ?>" alt="<?php echo $this->event->images->image_full_alt; ?>">
		<?php if ($this->event->images->image_full_caption) { ?>
			<figcaption class="dp-figure__caption"><?php echo $this->event->images->image_full_caption; ?></figcaption>
		<?php } ?>
	</figure>
</div>
