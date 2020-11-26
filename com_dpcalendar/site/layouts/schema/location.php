<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2017 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
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
				<?php if (!empty($location->city)) { ?>
					<meta itemprop="addressLocality" content="<?php echo $location->city; ?>">
				<?php } ?>
				<?php if (!empty($location->province)) { ?>
					<meta itemprop="addressRegion" content="<?php echo $location->province; ?>">
				<?php } ?>
				<?php if (!empty($location->zip)) { ?>
					<meta itemprop="postalCode" content="<?php echo $location->zip; ?>">
				<?php } ?>
				<?php if (!empty($location->street)) { ?>
					<meta itemprop="streetAddress" content="<?php echo $location->street . ' ' . $location->number; ?>">
				<?php } ?>
				<?php if (!empty($location->country_code_value)) { ?>
					<meta itemprop="addressCountry" content="<?php echo $location->country_code_value; ?>">
				<?php } ?>
			</div>
		</div>
	<?php } ?>
</div>
