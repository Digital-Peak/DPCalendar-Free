<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2018 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

\defined('_JEXEC') or die();

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Uri\Uri;

if (!$this->params->get('locations_expand', 1)) {
	return;
}
?>
<div class="com-dpcalendar-location__details dp-location">
	<h<?php echo $this->heading + 2; ?> class="dp-heading">
		<?php echo $this->translate('COM_DPCALENDAR_VIEW_EVENT_LOCATION_INFORMATION'); ?>
	</h<?php echo $this->heading + 2; ?>>
	<div class="dp-location__details">
		<?php if ($this->location->street) { ?>
			<dl class="dp-description">
				<dt class="dp-description__label"><?php echo $this->translate('COM_DPCALENDAR_LOCATION_FIELD_STREET_LABEL'); ?></dt>
				<dd class="dp-description__description dp-location__street">
					<?php if ($this->params->get('location_format', 'format_us') == 'format_us') { ?>
						<?php echo $this->location->number . ' ' . $this->location->street; ?>
					<?php } else { ?>
						<?php echo $this->location->street . ' ' . $this->location->number; ?>
					<?php } ?>
				</dd>
			</dl>
		<?php } ?>
		<?php if ($this->location->city) { ?>
			<dl class="dp-description">
				<dt class="dp-description__label"><?php echo $this->translate('COM_DPCALENDAR_LOCATION_FIELD_CITY_LABEL'); ?></dt>
				<dd class="dp-description__description dp-location__city">
					<?php if ($this->params->get('location_format', 'format_us') == 'format_us') { ?>
						<?php echo $this->location->city; ?>
					<?php } else { ?>
						<?php echo $this->location->zip . ' ' . $this->location->city; ?>
					<?php } ?>
				</dd>
			</dl>
		<?php } ?>
		<?php if ($this->location->province) { ?>
			<dl class="dp-description">
				<dt class="dp-description__label"><?php echo $this->translate('COM_DPCALENDAR_LOCATION_FIELD_PROVINCE_LABEL'); ?></dt>
				<dd class="dp-description__description dp-location__province">
					<?php if ($this->params->get('location_format', 'format_us') == 'format_us') { ?>
						<?php echo $this->location->province . ' ' . $this->location->zip; ?>
					<?php } else { ?>
						<?php echo $this->location->province; ?>
					<?php } ?>
				</dd>
			</dl>
		<?php } ?>
		<?php if (!empty($this->location->country_code_value)) { ?>
			<dl class="dp-description">
				<dt class="dp-description__label"><?php echo $this->translate('COM_DPCALENDAR_LOCATION_FIELD_COUNTRY_LABEL'); ?></dt>
				<dd class="dp-description__description dp-location__country"><?php echo $this->location->country_code_value; ?></dd>
			</dl>
		<?php } ?>
		<?php if ($this->location->rooms) { ?>
			<dl class="dp-description">
				<dt class="dp-description__label"><?php echo $this->translate('COM_DPCALENDAR_ROOMS'); ?></dt>
				<dd class="dp-description__description dp-location__rooms">
					<?php foreach ($this->location->rooms as $room) { ?>
						<div class="dp-location__room"><?php echo $room->title; ?></div>
					<?php } ?>
				</dd>
			</dl>
		<?php } ?>
		<?php if ($this->location->url) { ?>
			<?php $u = Uri::getInstance($this->location->url); ?>
			<dl class="dp-description">
				<dt class="dp-description__label"><?php echo $this->translate('COM_DPCALENDAR_FIELD_URL_LABEL'); ?></dt>
				<dd class="dp-description__description dp-location__url">
					<a href="<?php echo $this->location->url; ?>" class="dp-link"
					   target="<?php echo $u->getHost() && Uri::getInstance()->getHost() != $u->getHost() ? '_blank' : ''; ?>">
						<?php echo $this->location->url; ?>
					</a>
				</dd>
			</dl>
		<?php } ?>
	</div>
	<div class="dp-location__description">
		<?php echo trim(implode(
			"\n",
			$this->app->triggerEvent('onContentBeforeDisplay', ['com_dpcalendar.location', &$this->location, &$params, 0])
		)); ?>
		<?php echo HTMLHelper::_('content.prepare', $this->location->description ?: ''); ?>
	</div>
</div>
