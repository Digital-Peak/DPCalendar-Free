<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2018 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */

defined('_JEXEC') or die();

DPCalendarHelper::loadLibrary(array('chosen' => true));
JHtml::_('script', 'com_dpcalendar/iframe-resizer/iframeresizer-contentwindow.min.js', ['relative' => true], ['defer' => true]);

JHtml::addIncludePath(JPATH_COMPONENT . '/helpers/html');
JHtml::_('behavior.formvalidation');

$input = JFactory::getApplication()->input;

if ($input->getCmd('tmpl') == 'component')
{
	$bar = JToolbar::getInstance('toolbar');
	echo $bar->render();
}
?>
<script type="text/javascript">
	Joomla.submitbutton = function(task)
	{
		if (task == 'extcalendar.cancel' || document.formvalidator.isValid(document.id('extcalendar-form'))) {
			<?php echo $this->form->getField('description')->save(); ?>
			Joomla.submitform(task, document.getElementById('extcalendar-form'));
		}
		else {
			alert('<?php echo $this->escape(JText::_('JGLOBAL_VALIDATION_FORM_FAILED'));?>');
		}
	}
</script>

<form action="<?php echo JRoute::_('index.php?option=com_dpcalendar&layout=edit&id=' . (int) $this->item->id); ?>"
	method="post" name="adminForm" id="extcalendar-form" class="form-validate dp-container">
	<div class="form-horizontal">
		<?php
		echo $this->form->renderField('title');
		echo $this->form->renderField('color');
		echo $this->form->renderField('color_force');

		echo $this->loadTemplate('params');

		echo $this->form->renderField('description');
		echo $this->form->renderField('access');
		echo $this->form->renderField('access_content');
		echo $this->form->renderField('state');
		echo $this->form->renderField('language');
		echo $this->form->renderField('rules');
		echo $this->form->renderField('sync_date');
		echo $this->form->renderField('sync_token');

		echo $this->form->getInput('asset_id');
		?>
		<input type="hidden" name="task" value="" />
		<input type="hidden" name="dpplugin" value="<?php echo $input->get('dpplugin')?>" />
		<input type="hidden" name="tmpl" value="<?php echo $input->get('tmpl')?>" />
		<?php echo JHtml::_('form.token'); ?>
	</div>
</form>
