<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2018 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */
defined('_JEXEC') or die(); ?>

<div id="j-sidebar-container" class="span2">
		<?php echo $this->sidebar; ?>
	</div>
	<div id="j-main-container" class="span10">
		<h4>DPCalendar Help</h4>
		<p>DPCalendar is a powerfull native joomla calendar manager.</p>

		<h4>Documentation and Support</h4>
		<p>
		At <a href="https://joomla.digital-peak.com" target="_blank">joomla.digital-peak.com</a> you will find all the information about
		DPCalendar and a support plattform to post questions.
		</p>
		<div align="center" style="clear: both">
			<?php echo sprintf(JText::_('COM_DPCALENDAR_FOOTER'), JFactory::getApplication()->input->getVar('DPCALENDAR_VERSION'));?>
		</div>
</div>
