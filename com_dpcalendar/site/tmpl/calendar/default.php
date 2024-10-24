<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2014 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

\defined('_JEXEC') or die();

use Joomla\CMS\HTML\HTMLHelper;

if ($this->params->get('show_map', 1) && $this->params->get('map_provider', 'openstreetmap') != 'none' && $this->getLayout() != 'print') {
	$this->layoutHelper->renderLayout('block.map', $this->displayData);
}

$this->dpdocument->loadStyleFile('dpcalendar/views/calendar/default.css');
$this->dpdocument->loadScriptFile('views/calendar/default.js');
$this->dpdocument->addStyle($this->params->get('calendar_custom_css', ''));

$this->loadTemplate('options');
?>
<div class="com-dpcalendar-calendar<?php echo $this->pageclass_sfx ? ' com-dpcalendar-calendar-' . $this->pageclass_sfx : ''; ?>">
	<?php echo $this->layoutHelper->renderLayout('block.timezone', $this->displayData); ?>
	<?php echo $this->loadTemplate('heading'); ?>
	<div class="com-dpcalendar-calendar__custom-text">
		<?php echo HTMLHelper::_('content.prepare', $this->translate($this->params->get('textbefore', ''))); ?>
	</div>
	<div class="com-dpcalendar-calendar__loader">
		<?php echo $this->layoutHelper->renderLayout('block.loader', $this->displayData); ?>
	</div>
	<?php echo $this->loadTemplate('list'); ?>
	<?php echo $this->loadTemplate('calendar'); ?>
	<?php echo $this->loadTemplate('map'); ?>
	<div class="com-dpcalendar-calendar__custom-text">
		<?php echo HTMLHelper::_('content.prepare', $this->translate($this->params->get('textafter', ''))); ?>
	</div>
	<?php echo $this->loadTemplate('quickadd'); ?>
	<?php echo $this->loadTemplate('icons'); ?>
</div>
