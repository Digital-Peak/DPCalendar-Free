<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2014 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */
defined('_JEXEC') or die();

if ($params->get('show_map', 1)) {
	$layoutHelper->renderLayout('block.map', $displayData);
}

$document->loadScriptFile('dpcalendar/views/calendar/default.js');
$document->loadScriptFile('dpcalendar/views/calendar/default.js');
$document->loadStyleFile('default.css', 'mod_dpcalendar_mini');
$document->addStyle($params->get('custom_css'));

require JModuleHelper::getLayoutPath('mod_dpcalendar_mini', '_scripts');

$compact = $params->get('compact_events', 2) == 1 ? 'compact' : 'expanded';
?>
<div class="mod-dpcalendar-mini mod-dpcalendar-mini_<?php echo $compact; ?> mod-dpcalendar-mini-<?php echo $module->id; ?>">
	<div class="mod-dpcalendar-mini__loader">
		<?php echo $layoutHelper->renderLayout('block.loader', $displayData); ?>
	</div>
	<div class="mod-dpcalendar-mini__calendar dp-calendar"
		 data-popupwidth="<?php echo $params->get('popup_width'); ?>"
		 data-popupheight="<?php echo $params->get('popup_height'); ?>"
		 data-options="DPCalendar.module.mini.<?php echo $module->id; ?>.options"></div>
	<?php require JModuleHelper::getLayoutPath('mod_dpcalendar_mini', 'default_map'); ?>
	<?php require JModuleHelper::getLayoutPath('mod_dpcalendar_mini', 'default_icons'); ?>
</div>
