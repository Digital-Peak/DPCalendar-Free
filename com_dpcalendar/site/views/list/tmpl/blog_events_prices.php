<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2023 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

defined('_JEXEC') or die();

use DPCalendar\HTML\Block\Icon;

$event = $this->displayData['event'];

if ($event->capacity == 0) {
	return;
}
?>
<?php if ($this->params->get('list_show_capacity', 1)) { ?>
	<div class="dp-event__capacity">
		<?php echo $this->layoutHelper->renderLayout(
			'block.icon',
			['icon'  => Icon::USERS, 'title' => $this->translate('COM_DPCALENDAR_FIELD_CAPACITY_LABEL')]
		); ?>
		<?php if ($event->capacity === null) { ?>
			<?php echo $this->translate('COM_DPCALENDAR_FIELD_CAPACITY_UNLIMITED'); ?>
		<?php } else { ?>
			<?php echo $event->capacity_used . '/' . (int)$event->capacity; ?>
		<?php } ?>
	</div>
<?php } ?>
<div class="dp-event__price">
	<?php echo $this->layoutHelper->renderLayout(
		'block.icon',
		['icon' => Icon::MONEY, 'title' => $this->translate('COM_DPCALENDAR_FIELD_PRICE_LABEL')]
	); ?>
	<?php echo $this->translate($event->price ? 'COM_DPCALENDAR_VIEW_BLOG_PAID_EVENT' : 'COM_DPCALENDAR_VIEW_BLOG_FREE_EVENT'); ?>
</div>
