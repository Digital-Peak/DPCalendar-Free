<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2018 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */

defined('_JEXEC') or die();

$this->dpdocument->loadLibrary(\DPCalendar\HTML\Document\HtmlDocument::LIBRARY_DPCORE);
$this->dpdocument->loadLibrary(\DPCalendar\HTML\Document\HtmlDocument::LIBRARY_MAP);
$this->dpdocument->loadLibrary(\DPCalendar\HTML\Document\HtmlDocument::LIBRARY_FORM);
$this->dpdocument->loadLibrary(\DPCalendar\HTML\Document\HtmlDocument::LIBRARY_AUTOCOMPLETE);
$this->dpdocument->loadStyleFile('dpcalendar/views/locationform/default.css');
$this->dpdocument->loadScriptFile('dpcalendar/views/locationform/default.js');

$tmpl   = $this->input->getCmd('tmpl') ? '&tmpl=' . $this->input->getCmd('tmpl') : '';
$action = $this->router->route('index.php?option=com_dpcalendar&view=locationform&l_id=' . (int)$this->location->id . $tmpl);
?>
<div class="com-dpcalendar-locationform<?php echo $this->pageclass_sfx ? ' com-dpcalendar-locationform-' . $this->pageclass_sfx : ''; ?>">
	<?php echo $this->layoutHelper->renderLayout('block.timezone', $this->displayData); ?>
	<?php echo $this->loadTemplate('heading'); ?>
	<?php echo $this->loadTemplate('header'); ?>
	<form class="com-dpcalendar-locationform__form dp-form form-validate" method="post" name="adminForm" id="adminForm" action="<?php echo $action; ?>">
		<?php echo $this->loadTemplate('fields'); ?>
		<div class="com-dpcalendar-locationform__map dp-map"></div>
		<input type="hidden" name="task" class="dp-input dp-input-hidden">
		<input type="hidden" name="return" value="<?php echo $this->returnPage; ?>" class="dp-input dp-input-hidden">
		<input type="hidden" name="tmpl" value="<?php echo $this->input->get('tmpl'); ?>" class="dp-input dp-input-hidden">
		<?php echo JHtml::_('form.token'); ?>
	</form>
</div>
