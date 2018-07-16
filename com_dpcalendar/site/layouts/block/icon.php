<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2018 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */
defined('_JEXEC') or die();

$file = \Joomla\CMS\HTML\HTMLHelper::image('com_dpcalendar/icons/' . $displayData['icon'] . '.svg', '', null, true, 1);
if (!$file) {
	return;
}

$content = @file_get_contents(JPATH_ROOT . substr($file, strpos($file, '/media')));
if (!empty($displayData['title'])) {
	$content = str_replace('><path', '><title>' . $displayData['title'] . '</title><path', $content);
}
?>
<span class="dp-icon do-icon_<?php echo $displayData['icon']; ?>"><?php echo $content; ?></span>
