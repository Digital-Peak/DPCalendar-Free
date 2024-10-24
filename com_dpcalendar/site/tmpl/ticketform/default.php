<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2018 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

\defined('_JEXEC') or die();

use Joomla\CMS\HTML\HTMLHelper;

$this->dpdocument->loadStyleFile('dpcalendar/views/ticketform/default.css');
$this->dpdocument->loadScriptFile('views/ticketform/default.js');
$this->dpdocument->addStyle($this->params->get('ticket_form_custom_css', ''));

$this->translator->translateJS('COM_DPCALENDAR_OPTIONS');

$action = $this->router->route('index.php?option=com_dpcalendar&view=ticketform&t_id=' . (int)$this->ticket->id);
?>
<div class="com-dpcalendar-ticketform<?php echo $this->pageclass_sfx ? ' com-dpcalendar-ticketform-' . $this->pageclass_sfx : ''; ?>">
	<?php echo $this->layoutHelper->renderLayout('block.timezone', $this->displayData); ?>
	<?php echo $this->loadTemplate('heading'); ?>
	<form class="com-dpcalendar-ticketform__form dp-form form-validate" method="post" name="adminForm" id="adminForm" action="<?php echo $action; ?>">
		<?php echo $this->loadTemplate('fields'); ?>
		<input type="hidden" name="task" class="dp-input dp-input-hidden">
		<input type="hidden" name="return" value="<?php echo $this->returnPage; ?>" class="dp-input dp-input-hidden">
		<input type="hidden" name="tmpl" value="<?php echo $this->input->get('tmpl'); ?>" class="dp-input dp-input-hidden">
		<?php echo HTMLHelper::_('form.token'); ?>
	</form>
	<?php echo $this->loadTemplate('actions'); ?>
</div>
