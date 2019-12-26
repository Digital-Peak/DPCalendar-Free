<?php
/**
 * @package   DPCalendar
 * @author    Digital Peak http://www.digital-peak.com
 * @copyright Copyright (C) 2007 - 2019 Digital Peak. All rights reserved.
 * @license   http://www.gnu.org/licenses/gpl.html GNU/GPL
 */
defined('_JEXEC') or die();

$this->dpdocument->loadLibrary(\DPCalendar\HTML\Document\HtmlDocument::LIBRARY_DPCORE);
$this->dpdocument->loadLibrary(\DPCalendar\HTML\Document\HtmlDocument::LIBRARY_IFRAME_CHILD);

$plugin = $this->input->getCmd('dpplugin');
JFactory::getLanguage()->load('plg_dpcalendar_' . $plugin, JPATH_PLUGINS . '/dpcalendar/' . $plugin);
JForm::addFormPath(JPATH_PLUGINS . '/dpcalendar/' . $plugin . '/forms');
$form = JForm::getInstance('form', 'params');
?>
<form action="<?php echo htmlspecialchars(JUri::getInstance()) ?>" method="post" target="_parent">
	<?php foreach ($form->getFieldset('params') as $field) { ?>
		<?php if (!$form->getFieldAttribute(str_replace('params[', '', trim($field->__get('name'), ']')), 'import', null, 'params')) { ?>
			<?php continue; ?>
		<?php } ?>
		<div class="control-group">
			<div class="control-label">
				<?php echo $field->label; ?>
			</div>
			<div class="controls">
				<?php echo $field->input; ?>
				<br/><b><?php echo JText::_($field->description) ?></b>
			</div>
		</div>
	<?php } ?>
	<input type="hidden" name="task" value="plugin.action"/>
	<input type="hidden" name="action" value="import"/>
	<input type="submit" class="btn btn-primary" value="<?php echo JText::_('COM_DPCALENDAR_VIEW_TOOLS_IMPORT') ?>"/>
</form>
