<?php
/**
 * @package   DPCalendar
 * @author    Digital Peak http://www.digital-peak.com
 * @copyright Copyright (C) 2007 - 2020 Digital Peak. All rights reserved.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */
defined('_JEXEC') or die();

$url = str_replace(['?tmpl=component', 'tmpl=component'], '', $this->router->getEventRoute($this->event->id, $this->event->catid));
?>
<h<?php echo $this->heading + 1; ?> class="com-dpcalendar-event__title dp-heading">
	<?php if ($this->event->state == 3) { ?>
		<span class="com-dpcalendar-event__title_canceled">[<?php echo $this->translate('COM_DPCALENDAR_FIELD_VALUE_CANCELED'); ?>]</span>
	<?php } ?>
	<?php if ($this->input->get('tmpl') == 'component') { ?>
		<a href="<?php echo $url; ?>" class="com-dpcalendar-event__link" target="_parent"><?php echo $this->event->title; ?></a>
	<?php } else { ?>
		<?php echo $this->event->title; ?>
	<?php } ?>
</h<?php echo $this->heading + 1; ?>>
<div class="com-dpcalendar-event__event-text">
	<?php echo $this->event->displayEvent->afterDisplayTitle; ?>
</div>
