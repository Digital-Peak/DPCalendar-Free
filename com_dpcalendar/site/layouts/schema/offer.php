<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2017 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */
defined('_JEXEC') or die();

$event = $displayData['event'];
$price = $price = $event->price;
if (!$price || !is_array($price->value) || empty($price->value)) {
	$price              = new stdClass();
	$price->value       = [0];
	$price->label       = [''];
	$price->description = [''];
}
?>
<div itemprop="offers" itemtype="https://schema.org/AggregateOffer" itemscope>
	<meta itemprop="priceCurrency" content="<?php echo $displayData['params']->get('currency', 'USD'); ?>">
	<meta itemprop="offerCount" content="<?php echo $event->capacity; ?>">
	<meta itemprop="highPrice" content="<?php echo max($price->value); ?>">
	<meta itemprop="lowPrice" content="<?php echo min($price->value); ?>">
	<meta itemprop="availability"
		  content="https://schema.org/<?php echo $event->capacity > $event->capacity_used ? 'InStock' : 'SoldOut'; ?>">
	<meta itemprop="url" content="<?php echo $displayData['router']->getEventRoute($event->id, $event->catid, true, true); ?>">
	<meta itemprop="validFrom" content="<?php echo $displayData['dateHelper']->getDate($event->created)->format('c'); ?>">
	<?php foreach ($price->value as $key => $value) { ?>
		<div itemprop="offers" itemtype="https://schema.org/Offer" itemscope>
			<meta itemprop="price" content="<?php echo $value; ?>">
			<?php if ($price->label[$key]) { ?>
				<meta itemprop="name" content="<?php echo htmlentities($price->label[$key], ENT_COMPAT, 'UTF-8'); ?>">
			<?php } ?>
			<?php if ($price->description[$key]) { ?>
				<meta itemprop="description" content="<?php echo htmlentities(strip_tags($price->description[$key]), ENT_COMPAT, 'UTF-8'); ?>">
			<?php } ?>
		</div>
	<?php } ?>
</div>
