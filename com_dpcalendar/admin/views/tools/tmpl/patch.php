<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2019 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */
defined('_JEXEC') or die();

$this->dpdocument->loadStyleFile('dpcalendar/views/tools/patch.css');
?>
<form action="<?php echo JRoute::_('index.php?option=com_dpcalendar&task=import.patch'); ?>" method="post" name="adminForm" id="adminForm"
	  class="com-dpcalendar-tools-patch" enctype="multipart/form-data">
	<div id="j-sidebar-container" class="com-dpcalendar-tools-patch__sidebar span2"><?php echo $this->sidebar; ?></div>
	<div id="j-main-container" class="com-dpcalendar-tools-patch__content span10">
		<?php if ($this->canPatch) { ?>
			<input type="file" name="patch" class="dp-input-file"/>
			<label class="dp-revert">
				<input type="checkbox" name="revert" class="dp-input-checkbox"/>
				<?php echo $this->translate('COM_DPCALENDAR_VIEW_TOOLS_PATCH_REVERT'); ?>
			</label>
		<?php } ?>
		<div class="com-dpcalendar-tools-patch__footer">
			<?php echo sprintf(JText::_('COM_DPCALENDAR_FOOTER'), $this->input->getString('DPCALENDAR_VERSION')); ?>
		</div>
	</div>
	<input type="hidden" name="task" value=""/>
	<?php echo JHtml::_('form.token'); ?>
</form>
