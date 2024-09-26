<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2017 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

use Joomla\CMS\Factory;

defined('_JEXEC') or die();

$event = $displayData['event'];
$prices = (array)$event->price !== [] ? (array)$event->price : ['price0' => (object)['value' => 0, 'label' => '', 'description' => '', 'currency' => '']];
?>
<div itemprop="offers" itemtype="https://schema.org/AggregateOffer" itemscope>
	<meta itemprop="priceCurrency" content="<?php echo Factory::getApplication()->bootComponent('dpcalendar')->getMVCFactory()->createModel('Currency', 'Administrator')->getActualCurrency()->currency; ?>">
	<meta itemprop="offerCount" content="<?php echo $event->capacity; ?>">
	<meta itemprop="highPrice" content="<?php echo max(array_column($prices, 'value')); ?>">
	<meta itemprop="lowPrice" content="<?php echo min(array_column($prices, 'value')); ?>">
	<meta itemprop="availability"
		  content="https://schema.org/<?php echo $event->capacity > $event->capacity_used ? 'InStock' : 'SoldOut'; ?>">
	<meta itemprop="url" content="<?php echo $displayData['router']->getEventRoute($event->id, $event->catid, true, true); ?>">
	<meta itemprop="validFrom" content="<?php echo $displayData['dateHelper']->getDate($event->created)->format('c'); ?>">
	<?php foreach ($prices as $price) { ?>
		<div itemprop="offers" itemtype="https://schema.org/Offer" itemscope>
			<meta itemprop="price" content="<?php echo $price->value; ?>">
			<?php if (!empty($price->label)) { ?>
				<meta itemprop="name" content="<?php echo htmlentities((string)$price->label, ENT_COMPAT, 'UTF-8'); ?>">
			<?php } ?>
			<?php if (!empty($price->description)) { ?>
				<meta itemprop="description" content="<?php echo htmlentities(strip_tags((string)$price->description), ENT_COMPAT, 'UTF-8'); ?>">
			<?php } ?>
		</div>
	<?php } ?>
</div>
