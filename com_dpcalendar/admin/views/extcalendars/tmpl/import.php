<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2018 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */
defined('_JEXEC') or die();

JHtml::addIncludePath(JPATH_COMPONENT . '/helpers/html');
JHtml::_('script', 'com_dpcalendar/iframe-resizer/iframeresizer-contentwindow.min.js', ['relative' => true], ['defer' => true]);

$plugin = JFactory::getApplication()->input->getCmd('dpplugin');
JFactory::getLanguage()->load('plg_dpcalendar_' . $plugin, JPATH_PLUGINS . '/dpcalendar/' . $plugin);
JForm::addFormPath(JPATH_PLUGINS . '/dpcalendar/' . $plugin.'/forms');
$form = JForm::getInstance('form', 'params');
$uri = JUri::getInstance();
?>
<form action="<?php echo htmlspecialchars($uri)?>" method="post" class="form-horizontal"
	target="_parent">
<?php
foreach ($form->getFieldset('params') as $field)
{
	if (! $form->getFieldAttribute(str_replace('params[', '', trim($field->__get('name'), ']')), 'import', null, 'params'))
	{
		continue;
	}
	?>
	<div class="control-group">
		<div class="control-label">
			<?php echo $field->label; ?>
		</div>
		<div class="controls">
			<?php echo $field->input;?>
			<br/><b><?php echo JText::_($field->description)?></b>
		</div>
	</div><?php
}
?>
<input type="hidden" name="task" value="plugin.action" />
<input type="hidden" name="action" value="import" />
<input type="submit" class="btn btn-primary" value="<?php echo JText::_('COM_DPCALENDAR_VIEW_TOOLS_IMPORT')?>" />
</form>
