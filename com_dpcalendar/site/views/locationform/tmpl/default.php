<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2018 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

defined('_JEXEC') or die();

use Joomla\CMS\HTML\HTMLHelper;

if ($this->params->get('location_form_show_map', 1) && $this->params->get('map_provider', 'openstreetmap') != 'none') {
	$this->layoutHelper->renderLayout('block.map', $this->displayData);
}

$this->dpdocument->loadStyleFile('dpcalendar/views/locationform/default.css');
$this->dpdocument->loadScriptFile('dpcalendar/views/locationform/default.js');
$this->dpdocument->addStyle($this->params->get('location_form_custom_css'));

$this->translator->translateJS('COM_DPCALENDAR_OPTIONS');

$action = $this->router->route('index.php?option=com_dpcalendar&view=locationform&l_id=' . (int)$this->location->id . $this->tmpl);
?>
<div class="com-dpcalendar-locationform<?php echo $this->pageclass_sfx ? ' com-dpcalendar-locationform-' . $this->pageclass_sfx : ''; ?>">
	<?php echo $this->layoutHelper->renderLayout('block.timezone', $this->displayData); ?>
	<?php echo $this->loadTemplate('heading'); ?>
	<form class="com-dpcalendar-locationform__form dp-form form-validate" method="post" name="adminForm" id="adminForm"
		  action="<?php echo $action; ?>">
		<?php if ($this->params->get('location_form_flat_mode')) { ?>
			<?php echo $this->loadTemplate('flat_fields'); ?>
		<?php } else { ?>
			<?php echo $this->loadTemplate('tabbed_fields'); ?>
		<?php } ?>
		<?php echo $this->loadTemplate('map'); ?>
		<input type="hidden" name="task" class="dp-input dp-input-hidden">
		<input type="hidden" name="return" value="<?php echo $this->returnPage; ?>" class="dp-input dp-input-hidden">
		<input type="hidden" name="tmpl" value="<?php echo $this->input->get('tmpl'); ?>" class="dp-input dp-input-hidden">
		<?php echo HTMLHelper::_('form.token'); ?>
	</form>
	<?php echo $this->loadTemplate('actions'); ?>
</div>
