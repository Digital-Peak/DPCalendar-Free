<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2018 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */

defined('_JEXEC') or die();

$document->loadLibrary(\DPCalendar\HTML\Document\HtmlDocument::LIBRARY_FULLCALENDAR);

if ($params->get('show_event_as_popup')) {
	$document->loadLibrary(\DPCalendar\HTML\Document\HtmlDocument::LIBRARY_MODAL);
}

$document->loadStyleFile('default.css', 'mod_dpcalendar_mini');

require JModuleHelper::getLayoutPath('mod_dpcalendar_mini', '_scripts');
?>
<div class="mod-dpcalendar-mini mod-dpcalendar-mini-<?php echo $module->id; ?>">
	<div class="mod-dpcalendar-mini__loader">
		<?php echo $layoutHelper->renderLayout('block.loader', $displayData); ?>
	</div>
	<div class="mod-dpcalendar-mini__calendar dp-calendar"
		 data-popupwidth="<?php echo $params->get('popup_width'); ?>"
		 data-popupheight="<?php echo $params->get('popup_height', 500); ?>"
		 data-options="DPCalendar.module.mini.<?php echo $module->id; ?>.options"></div>
</div>
