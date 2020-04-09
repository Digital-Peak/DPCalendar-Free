<?php
/**
 * @package   DPCalendar
 * @author    Digital Peak http://www.digital-peak.com
 * @copyright Copyright (C) 2007 - 2020 Digital Peak. All rights reserved.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */
defined('_JEXEC') or die();

$this->dpdocument->loadStyleFile('dpcalendar/views/invite/default.css');
$this->dpdocument->loadScriptFile('dpcalendar/views/invite/default.js');
?>
<div class="com-dpcalendar-invite<?php echo $this->pageclass_sfx ? ' com-dpcalendar-invite-' . $this->pageclass_sfx : ''; ?>">
	<?php echo $this->layoutHelper->renderLayout('block.timezone', $this->displayData); ?>
	<?php echo $this->loadTemplate('heading'); ?>
	<?php echo $this->loadTemplate('form'); ?>
	<?php echo $this->loadTemplate('actions'); ?>
</div>
