<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2021 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

\defined('_JEXEC') or die();

$this->dpdocument->loadStyleFile('dpcalendar/views/event/mailtickets.css');
$this->dpdocument->loadScriptFile('views/event/mailtickets.js');
?>
<div class="com-dpcalendar-event-mailtickets<?php echo $this->pageclass_sfx ? ' com-dpcalendar-event-mailtickets-' . $this->pageclass_sfx : ''; ?>">
	<?php echo $this->loadTemplate('heading'); ?>
	<?php echo $this->loadTemplate('form'); ?>
	<?php echo $this->loadTemplate('actions'); ?>
</div>
