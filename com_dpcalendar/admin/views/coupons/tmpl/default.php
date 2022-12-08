<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2020 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */
defined('_JEXEC') or die();

$this->dpdocument->loadStyleFile('dpcalendar/views/adminlist/default.css');
$this->dpdocument->loadScriptFile('dpcalendar/views/adminlist/default.js');
$this->dpdocument->addScriptOptions('adminlist', ['listOrder' => $this->state->get('list.ordering')]);
?>
<div class="com-dpcalendar-coupons com-dpcalendar-adminlist">
	<form action="<?php echo $this->router->route('index.php?option=com_dpcalendar&view=coupons'); ?>"
		  method="post" name="adminForm" id="adminForm">
		<?php if ($this->sidebar) { ?>
			<div id="j-sidebar-container"><?php echo $this->sidebar; ?></div>
		<?php } ?>
		<div id="j-main-container">
			<?php echo $this->layoutHelper->renderLayout('joomla.searchtools.default', ['view' => $this]); ?>
			<?php echo $this->loadTemplate('coupons'); ?>
		</div>
		<input type="hidden" name="task" value=""/>
		<input type="hidden" name="boxchecked" value="0"/>
		<?php echo JHtml::_('form.token'); ?>
	</form>
	<?php echo $this->loadTemplate('footer'); ?>
</div>
