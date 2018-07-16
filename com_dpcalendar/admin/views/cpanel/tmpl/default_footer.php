<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2018 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */

defined('_JEXEC') or die();

$url = 'http://extensions.joomla.org/extensions/extension/calendars-a-events/events/dpcalendar' . DPCalendarHelper::isFree() ? '-lite' : '';
?>
<div class="com-dpcalendar-cpanel__footer dp-footer">
	<div class="dp-footer__version">
		<?php echo JText::sprintf('COM_DPCALENDAR_FOOTER', $this->input->getString('DPCALENDAR_VERSION')); ?>
	</div>
	<div class="dp-footer__jed">
		<span class="small">If you like DPCalendar, please post a positive review at the</span>
		<a href="<?php echo $url; ?>" target="_blank">Joomla! Extensions Directory</a>
	</div>
</div>
