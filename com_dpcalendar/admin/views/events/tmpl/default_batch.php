<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2018 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */

defined('_JEXEC') or die();

?>
<div class="modal hide fade" id="collapseModal">
	<div class="modal-header">
		<button type="button" class="close" data-dismiss="modal">x</button>
		<h3><?php echo JText::_('COM_DPCALENDAR_BATCH_OPTIONS'); ?></h3>
	</div>
	<div class="modal-body" style="padding: 20px;">
		<p><?php echo JText::_('COM_DPCALENDAR_BATCH_TIP'); ?></p>
		<div class="control-group">
			<div class="controls">
				<?php echo JLayoutHelper::render('joomla.html.batch.access', []); ?>
			</div>
		</div>
		<div class="control-group">
			<div class="controls">
				<?php echo JLayoutHelper::render('joomla.html.batch.language', []); ?>
			</div>
		</div>
		<div class="control-group">
			<div class="controls">
				<label title="<?php echo JText::_('COM_DPCALENDAR_BATCH_COLOR_DESC'); ?>"
					   class="hasTooltip" for="batch-color" id="batch-color-lbl"><?php echo JText::_('COM_DPCALENDAR_COLOR'); ?></label>
				<input id="batch-color-id" class="color {required:false} inputbox" name="batch[color_id]" maxlength="6"/>
			</div>
		</div>
		<div class="control-group">
			<div class="controls">
				<?php echo JLayoutHelper::render('joomla.html.batch.tag', []); ?>
			</div>
		</div>
		<?php if ($this->state->get('filter.state') >= 0) { ?>
			<div class="control-group">
				<div class="controls">
					<?php echo JLayoutHelper::render('joomla.html.batch.language', ['extension' => 'com_dpcalendar']); ?>
				</div>
			</div>
		<?php } ?>
		<div class="control-group">
			<div class="controls">
				<?php echo JHtml::_('batch.item', 'com_dpcalendar'); ?>
			</div>
		</div>
	</div>
	<div class="modal-footer">
		<button class="btn" type="button"
				onclick="document.id('batch-category-id').value='';document.id('batch-access').value='';document.id('batch-language-id').value='';document.id('batch-tag-id)').value=''"
				data-dismiss="modal">
			<?php echo JText::_('JCANCEL'); ?>
		</button>
		<button class="btn btn-primary" type="submit" onclick="Joomla.submitbutton('event.batch');">
			<?php echo JText::_('JGLOBAL_BATCH_PROCESS'); ?>
		</button>
	</div>
</div>
