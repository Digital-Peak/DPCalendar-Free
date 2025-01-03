<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2015 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

\defined('_JEXEC') or die();

$this->dpdocument->loadStyleFile('dpcalendar/views/invite/default.css');
$this->dpdocument->loadScriptFile('views/invite/default.js');

$this->translator->translateJS('COM_DPCALENDAR_OPTIONS');
?>
<div class="com-dpcalendar-invite<?php echo $this->pageclass_sfx ? ' com-dpcalendar-invite-' . $this->pageclass_sfx : ''; ?>">
	<?php echo $this->layoutHelper->renderLayout('block.timezone', $this->displayData); ?>
	<?php echo $this->loadTemplate('heading'); ?>
	<?php echo $this->loadTemplate('form'); ?>
	<?php echo $this->loadTemplate('actions'); ?>
</div>
