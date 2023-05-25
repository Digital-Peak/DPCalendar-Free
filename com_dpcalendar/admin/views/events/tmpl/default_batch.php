<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2014 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */
defined('_JEXEC') or die();

if (\DPCalendar\Helper\DPCalendarHelper::isJoomlaVersion('4', '>=')) {
	return false;
}
?>
<div class="modal hide fade com-dpcalendar-adminlist__batch" id="collapseModal">
	<div class="modal-header">
		<button type="button" class="close" data-dismiss="modal">x</button>
		<h3><?php echo $this->translate('COM_DPCALENDAR_BATCH_OPTIONS'); ?></h3>
	</div>
	<div class="modal-body">
		<p><?php echo $this->translate('COM_DPCALENDAR_BATCH_TIP'); ?></p>
		<div class=" dp-grid">
			<div class="dp-grid__col-6">
				<div class="control-group">
					<div class="controls"><?php echo JLayoutHelper::render('joomla.html.batch.access', []); ?></div>
				</div>
				<div class="control-group">
					<div class="controls">
						<label title="<?php echo $this->translate('COM_DPCALENDAR_BATCH_ACCESS_CONTENT_DESC'); ?>"
							   class="hasTooltip" for="batch-access-content" id="batch-access-content-lbl">
							<?php echo $this->translate('COM_DPCALENDAR_FIELD_ACCESS_CONTENT_LABEL'); ?>
						</label>
						<?php echo JHtml::_(
							'access.assetgrouplist',
							'batch[access_content_id]',
							'',
							'class="inputbox"',
							[
								'title' => $this->translate('JLIB_HTML_BATCH_NOCHANGE'),
								'id'    => 'batch-access-content'
							]
						); ?>
					</div>
				</div>
				<div class="control-group">
					<div class="controls">
						<label class="hasTooltip" for="batch-capacity" id="batch-capacity-lbl">
							<?php echo $this->translate('COM_DPCALENDAR_FIELD_CAPACITY_LABEL'); ?>
						</label>
						<input id="batch-capacity-id" class="inputbox" name="batch[capacity_id]"/>
					</div>
				</div>
			</div>
			<div class="dp-grid__col-6">
				<div class="control-group">
					<div class="controls">
						<label title="<?php echo $this->translate('COM_DPCALENDAR_BATCH_COLOR_DESC'); ?>"
							   class="hasTooltip" for="batch-color" id="batch-color-lbl">
							<?php echo $this->translate('COM_DPCALENDAR_COLOR'); ?>
						</label>
						<input id="batch-color-id" class="color inputbox" name="batch[color_id]" maxlength="6"/>
					</div>
				</div>
				<div class="control-group">
					<div class="controls"><?php echo JLayoutHelper::render('joomla.html.batch.tag', []); ?></div>
				</div>
				<div class="control-group">
					<div class="controls"><?php echo JLayoutHelper::render('joomla.html.batch.language', ['extension' => 'com_dpcalendar']); ?></div>
				</div>
			</div>
		</div>
		<div class="control-group dp-batch-calendar">
			<?php echo JHtml::_('batch.item', 'com_dpcalendar'); ?>
		</div>
	</div>
	<div class="modal-footer">
		<button class="btn dp-button-close" type="button" data-dismiss="modal"><?php echo $this->translate('JCANCEL'); ?></button>
		<button class="dp-button-submit btn btn-primary" type="submit"><?php echo $this->translate('JGLOBAL_BATCH_PROCESS'); ?></button>
	</div>
</div>
