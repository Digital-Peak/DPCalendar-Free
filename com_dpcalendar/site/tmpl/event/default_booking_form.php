<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2021 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

use DigitalPeak\Component\DPCalendar\Site\Controller\BookingformController;

defined('_JEXEC') or die();

if (!$this->params->get('event_show_booking_form')) {
	return;
}

$controller = new BookingformController([],$this->app->bootComponent('dpcalendar')->getMVCFactory(), $this->app, $this->app->getInput());
if (!$controller->canAdd([['event_id' => $this->event->id]])) {
	return;
}
?>
<div class="com-dpcalendar-event__booking-form<?php echo $this->params->get('event_show_booking_form') == 1 ? ' dp-toggle_hidden' : ''; ?>">
	<?php $controller->display(); ?>
</div>
