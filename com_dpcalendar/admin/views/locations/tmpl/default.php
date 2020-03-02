<?php
/**
 * @package   DPCalendar
 * @author    Digital Peak http://www.digital-peak.com
 * @copyright Copyright (C) 2007 - 2020 Digital Peak. All rights reserved.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */
defined('_JEXEC') or die();

$this->dpdocument->loadStyleFile('dpcalendar/views/adminlist/default.css');
$this->dpdocument->loadScriptFile('dpcalendar/views/adminlist/default.js');
$this->dpdocument->addScriptOptions('adminlist', ['listOrder' => $this->state->get('list.ordering')]);
?>
<div class="com-dpcalendar-locations com-dpcalendar-adminlist">
	<form action="<?php echo $this->router->route('index.php?option=com_dpcalendar&view=locations'); ?>"
		  method="post" name="adminForm" id="adminForm" class="row">
		<div id="j-sidebar-container" class="span2 col-md-2"><?php echo $this->sidebar; ?></div>
		<div id="j-main-container" class="span10 col-md-10 j-toggle-main">
			<?php echo $this->layoutHelper->renderLayout('joomla.searchtools.default', ['view' => $this]); ?>
			<?php echo $this->loadTemplate('locations'); ?>
		</div>
		<?php echo $this->loadTemplate('batch'); ?>
		<input type="hidden" name="task" value=""/>
		<input type="hidden" name="boxchecked" value="0"/>
		<?php echo JHtml::_('form.token'); ?>
	</form>
	<?php echo $this->loadTemplate('footer'); ?>
</div>
