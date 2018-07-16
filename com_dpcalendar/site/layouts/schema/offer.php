<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2018 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */
defined('_JEXEC') or die();

if (!is_array($displayData['event']->price) || empty($displayData['event']->price)) {
	return;
}

$currency = $displayData['params']->get('currency', 'USD');
?>
<div itemprop="offers" itemtype="https://schema.org/AggregateOffer" itemscope>
	<?php foreach ($displayData['event']->price->value as $key => $value) { ?>
		<div itemprop="offers" itemtype="https://schema.org/Offer" itemscope>
			<meta itemprop="price" content="<?php echo $value; ?>">
			<meta itemprop="priceCurrency" content="<?php echo $currency; ?>">
			<meta itemprop="validFrom" content="<?php echo $displayData['dateHelper']->getDate($event->created)->format('c'); ?>">
			<?php if ($event->price->label[$key]) { ?>
				<meta itemprop="name" content="<?php echo $event->price->label[$key]; ?>">
			<?php } ?>
			<?php if ($event->price->description[$key]) { ?>
				<meta itemprop="description" content="<?php echo strip_tags($event->price->description[$key]); ?>">
			<?php } ?>
			<meta itemprop="availability"
			      content="<?php echo $displayData['translator']->translate('COM_DPCALENDAR_FIELD_CAPACITY_LABEL') . ': ' . $event->capacity; ?>">
			<meta itemprop="url" content="<?php echo $displayData['router']->getEventRoute($event->id, $event->catid, true, true); ?>">
		</div>
	<?php } ?>
</div>
