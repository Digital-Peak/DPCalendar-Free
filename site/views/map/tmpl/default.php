<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2014 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */
defined('_JEXEC') or die();

$this->layoutHelper->renderLayout('block.map', $this->displayData);

$this->dpdocument->loadStyleFile('dpcalendar/views/map/default.css');
$this->dpdocument->loadScriptFile('dpcalendar/views/map/default.js');
$this->dpdocument->addStyle($this->params->get('map_custom_css'));

$this->translator->translateJS('COM_DPCALENDAR_FIELD_CONFIG_EVENT_LABEL_NO_EVENT_TEXT');
$this->translator->translateJS('COM_DPCALENDAR_CLOSE');
?>
<div class="com-dpcalendar-map dp-search-map<?php echo $this->pageclass_sfx ? ' com-dpcalendar-map-' . $this->pageclass_sfx : ''; ?>"
	 data-popup="<?php echo $this->params->get('map_show_event_as_popup'); ?>"
	 data-popupwidth="<?php echo $this->params->get('map_popup_width'); ?>"
	 data-popupheight="<?php echo $this->params->get('map_popup_height'); ?>">
	<?php echo $this->layoutHelper->renderLayout('block.timezone', $this->displayData); ?>
	<?php echo $this->loadTemplate('heading'); ?>
	<div class="com-dpcalendar-map__loader">
		<?php echo $this->layoutHelper->renderLayout('block.loader', $this->displayData); ?>
	</div>
	<?php echo $this->loadTemplate('form'); ?>
	<?php echo $this->loadTemplate('map'); ?>
</div>
