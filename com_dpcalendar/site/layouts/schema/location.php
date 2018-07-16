<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2018 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */
defined('_JEXEC') or die();
if (empty($displayData['event']->locations)) {
	return;
}
?>
<div class="dpcalendar-schema-event-location">
	<?php foreach ((array)$displayData['event']->locations as $location) { ?>
		<div itemprop="location" itemtype="https://schema.org/Place" itemscope>
			<meta itemprop="name" content="<?php echo $location->title; ?>">
			<div itemprop="address" itemtype="https://schema.org/PostalAddress" itemscope>
				<?php if (isset($location->city) && $location->city) { ?>
					<meta itemprop="addressLocality" content="<?php echo $location->city; ?>">
				<?php } ?>
				<?php if (isset($location->province) && $location->province) { ?>
					<meta itemprop="addressRegion" content="<?php echo $location->province; ?>">
				<?php } ?>
				<?php if (isset($location->zip) && $location->zip) { ?>
					<meta itemprop="postalCode" content="<?php echo $location->zip; ?>">
				<?php } ?>
				<?php if (isset($location->street) && $location->street) { ?>
					<meta itemprop="streetAddress" content="<?php echo $location->street . ' ' . $location->number; ?>">
				<?php } ?>
				<?php if (isset($location->country) && $location->country) { ?>
					<meta itemprop="addressCountry" content="<?php echo $location->country; ?>">
				<?php } ?>
			</div>
		</div>
	<?php } ?>
</div>
