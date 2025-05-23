<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2014 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

\defined('_JEXEC') or die();

use Joomla\CMS\HTML\HTMLHelper;

if ($this->params->get('list_show_map', 1) && $this->params->get('map_provider', 'openstreetmap') != 'none') {
	$this->layoutHelper->renderLayout('block.map', $this->displayData);
}

$this->dpdocument->loadStyleFile('dpcalendar/views/list/default.css');
$this->dpdocument->loadScriptFile('views/list/default.js');
$this->dpdocument->addStyle($this->params->get('list_custom_css', ''));
?>
<div class="com-dpcalendar-list dp-locations<?php echo $this->pageclass_sfx ? ' com-dpcalendar-list-' . $this->pageclass_sfx : ''; ?>">
	<?php echo $this->layoutHelper->renderLayout('block.timezone', $this->displayData); ?>
	<?php echo $this->loadTemplate('heading'); ?>
	<div class="com-dpcalendar-list__custom-text">
		<?php echo HTMLHelper::_('content.prepare', $this->translate($this->params->get('list_textbefore', ''))); ?>
	</div>
	<?php echo $this->loadTemplate('map'); ?>
	<?php echo $this->loadTemplate('header'); ?>
	<?php echo $this->loadTemplate('form'); ?>
	<?php echo $this->loadTemplate('events'); ?>
	<div class="com-dpcalendar-list__custom-text">
		<?php echo HTMLHelper::_('content.prepare', $this->translate($this->params->get('list_textafter', ''))); ?>
	</div>
</div>
