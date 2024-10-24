<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2017 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

\defined('_JEXEC') or die();

$event     = $displayData['event'];
$locations = empty($event->locations) ? [] : (array)$event->locations;
if ($locations === []) {
	$url       = $event->url ?: $displayData['router']->getEventRoute($event->id, $event->catid, true, true);
	$locations = [(object)['url' => $url]];
}
?>
<div class="dpcalendar-schema-event-location">
	<?php foreach ($locations as $location) { ?>
		<div itemprop="location" itemtype="https://schema.org/<?php echo empty($location->title) ? 'VirtualLocation' : 'Place'; ?>" itemscope>
			<?php if (!empty($location->url)) { ?>
				<meta itemprop="url" content="<?php echo $location->url; ?>">
			<?php } ?>
			<?php if (!empty($location->title)) { ?>
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
						<meta itemprop="streetAddress" content="<?php echo $location->street . (empty($location->number) ? '' : ' ' . $location->number); ?>">
					<?php } ?>
					<?php if (!empty($location->country_code_value)) { ?>
						<meta itemprop="addressCountry" content="<?php echo $location->country_code_value; ?>">
					<?php } ?>
				</div>
			<?php } ?>
		</div>
	<?php } ?>
</div>
