<?php
use DPCalendar\Helper\DPCalendarHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2014 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */
defined('_JEXEC') or die();

if (DPCalendarHelper::isJoomlaVersion('4', '>=')) {
	return false;
}

JLoader::import('components.com_dpcalendar.models.fields.dpcountries', JPATH_ADMINISTRATOR);
$field = new JFormFieldDpcountries();
?>
<div class="modal hide fade com-dpcalendar-adminlist__batch" id="collapseModal">
	<div class="modal-header">
		<button type="button" role="presentation" class="close" data-dismiss="modal">x</button>
		<h3><?php echo Text::_('COM_DPCALENDAR_BATCH_OPTIONS'); ?></h3>
	</div>
	<div class="modal-body">
		<p><?php echo Text::_('COM_DPCALENDAR_BATCH_TIP'); ?></p>
		<div class="control-group">
			<div class="controls">
				<?php echo LayoutHelper::render('joomla.html.batch.language', []); ?>
			</div>
		</div>
		<div class="control-group">
			<div class="controls">
				<label class="hasTooltip" for="batch-country" id="batch-country-lbl">
					<?php echo $this->translate('COM_DPCALENDAR_LOCATION_FIELD_COUNTRY_LABEL'); ?>
				</label>
				<select name="batch[country_id]" id="batch-country-id">
					<?php foreach ($field->getOptions() as $option) { ?>
						<option value="<?php echo $option->value; ?>"><?php echo $option->text; ?></option>
					<?php } ?>
				</select>
			</div>
		</div>
	</div>
	<div class="modal-footer">
		<button class="btn btn-primary" type="submit" onclick="Joomla.submitbutton('location.batch');">
			<?php echo Text::_('JGLOBAL_BATCH_PROCESS'); ?>
		</button>
	</div>
</div>
