<?php
use Joomla\CMS\HTML\HTMLHelper;
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2018 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */
defined('_JEXEC') or die();

$this->dpdocument->loadStyleFile('dpcalendar/views/davcalendar/default.css');
$this->dpdocument->loadScriptFile('dpcalendar/views/davcalendar/default.js');

$action = $this->router->route('index.php?option=com_dpcalendar&view=profile&c_id=' . (int)$this->item->id);
?>
<div class="com-dpcalendar-davcalendar<?php echo $this->pageclass_sfx ? ' com-dpcalendar-davcalendar-' . $this->pageclass_sfx : ''; ?>">
	<?php echo $this->layoutHelper->renderLayout('block.timezone', $this->displayData); ?>
	<?php echo $this->loadTemplate('heading'); ?>
	<form class="com-dpcalendar-davcalendar__form dp-form form-validate" method="post" name="adminForm" id="adminForm"
		  action="<?php echo $action; ?>">
		<?php echo $this->loadTemplate('fields'); ?>
		<input type="hidden" name="task" class="dp-input dp-input-hidden">
		<input type="hidden" name="return" value="<?php echo $this->returnPage; ?>" class="dp-input dp-input-hidden">
		<input type="hidden" name="tmpl" value="<?php echo $this->input->get('tmpl'); ?>" class="dp-input dp-input-hidden">
		<?php echo HTMLHelper::_('form.token'); ?>
		<?php echo $this->loadTemplate('actions'); ?>
	</form>
</div>
