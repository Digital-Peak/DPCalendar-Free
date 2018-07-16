<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2018 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */
defined('_JEXEC') or die();
?>
<div class="com-dpcalendar-eventform__location">
	<div class="dp-map"
	     data-zoom="<?php echo $this->params->get('map_zoom', 6); ?>"
	     data-latitude="<?php echo $this->params->get('map_lat', 47); ?>"
	     data-longitude="<?php echo $this->params->get('map_long', 4); ?>">
	</div>
	<h3 class="dp-heading"><?php echo $this->translate('COM_DPCALENDAR_VIEW_EVENT_FORM_CREATE_LOCATION'); ?>
		<span class="com-dpcalendar-eventform__toggle dp-toggle">
			<span class="dp-toggle__up dp-toggle_hidden" data-direction="up">
				<?php echo $this->layoutHelper->renderLayout('block.icon', ['icon' => \DPCalendar\HTML\Block\Icon::UP]); ?>
			</span>
			<span class="dp-toggle__down" data-direction="down">
				<?php echo $this->layoutHelper->renderLayout('block.icon', ['icon' => \DPCalendar\HTML\Block\Icon::DOWN]); ?>
			</span>
		</span>
	</h3>
	<div class="com-dpcalendar-eventform__location-form">
		<button type="button" class="dp-button dp-button-save">
			<?php echo $this->layoutHelper->renderLayout('block.icon', ['icon' => \DPCalendar\HTML\Block\Icon::OK]); ?>
			<?php echo $this->translate('JAPPLY'); ?>
		</button>
		<button type="button" class="dp-button dp-button-cancel">
			<?php echo $this->layoutHelper->renderLayout('block.icon', ['icon' => \DPCalendar\HTML\Block\Icon::CANCEL]); ?>
			<?php echo $this->translate('JCANCEL'); ?>
		</button>
		<?php echo $this->locationForm->renderField('title'); ?>
		<?php echo $this->locationForm->renderField('country'); ?>
		<?php echo $this->locationForm->renderField('province'); ?>
		<?php echo $this->locationForm->renderField('city'); ?>
		<?php echo $this->locationForm->renderField('zip'); ?>
		<?php echo $this->locationForm->renderField('street'); ?>
		<?php echo $this->locationForm->renderField('number'); ?>
		<?php echo $this->locationForm->renderField('telephone'); ?>
		<?php echo $this->locationForm->renderField('url'); ?>
		<input type="hidden" name="location_token" value="<?php echo JSession::getFormToken(); ?>" class="dp-input dp-input-hidden">
	</div>
</div>
