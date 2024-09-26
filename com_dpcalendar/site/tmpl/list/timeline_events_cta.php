<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2023 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

defined('_JEXEC') or die();

use DigitalPeak\Component\DPCalendar\Administrator\Helper\Booking;
use DigitalPeak\Component\DPCalendar\Administrator\HTML\Block\Icon;

$event = $this->displayData['event'];

if (!$this->params->get('list_show_booking', 1) || !Booking::openForBooking($event)) {
	return;
}
?>
<a href="<?php echo $this->router->getBookingFormRouteFromEvent($event, $this->returnPage); ?>" class="dp-link dp-link_cta">
	<?php echo $this->layoutHelper->renderLayout(
		'block.icon',
		['icon' => Icon::BOOK, 'title' => $this->translate('COM_DPCALENDAR_BOOK')]
	); ?>
	<span class="dp-link__text">
		<?php echo $this->translate('COM_DPCALENDAR_VIEW_EVENT_TO_BOOK_TEXT'); ?>
	</span>
</a>
