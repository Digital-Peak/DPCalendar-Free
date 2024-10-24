<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2014 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

\defined('_JEXEC') or die();

$this->dpdocument->loadStyleFile('dpcalendar/views/profile/default.css');
$this->dpdocument->loadScriptFile('views/profile/default.js');
$this->dpdocument->addStyle($this->params->get('profile_custom_css', ''));

$this->translator->translateJS('COM_DPCALENDAR_CONFIRM_DELETE');
$this->translator->translateJS('COM_DPCALENDAR_VIEW_DAVCALENDAR_NONE_SELECTED_LABEL');
?>
<div class="com-dpcalendar-profile<?php echo $this->pageclass_sfx ? ' com-dpcalendar-profile-' . $this->pageclass_sfx : ''; ?>">
	<?php echo $this->layoutHelper->renderLayout('block.timezone', $this->displayData); ?>
	<?php echo $this->loadTemplate('heading'); ?>
	<?php echo $this->loadTemplate('sharing'); ?>
	<?php echo $this->loadTemplate('title'); ?>
	<?php echo $this->loadTemplate('form'); ?>
	<?php echo $this->loadTemplate('calendars'); ?>
	<?php echo $this->loadTemplate('footer'); ?>
	<?php echo $this->loadTemplate('events'); ?>
</div>
