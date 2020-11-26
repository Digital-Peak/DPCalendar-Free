<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2018 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */
defined('_JEXEC') or die();

$icon = basename($displayData['icon']);
$path = JPATH_ROOT . '/templates/' . JFactory::getApplication()->getTemplate() . '/images/com_dpcalendar/icons/' . $icon . '.svg';
if (!file_exists($path)) {
	$path = JPATH_ROOT . '/templates/' . JFactory::getApplication()->getTemplate() . '/images/icons/' . $icon . '.svg';
}
if (!file_exists($path)) {
	$path = JPATH_ROOT . '/media/com_dpcalendar/images/icons/' . $icon . '.svg';
}
if (!file_exists($path)) {
	return '';
}

if (in_array($path, \DPCalendar\HTML\Block\Icon::$pathCache)) {
	$content = '<svg><use href="#dp-icon-' . $icon . '"/></svg>';
} else {
	\DPCalendar\HTML\Block\Icon::$pathCache[] = $path;

	$content = @file_get_contents($path);
	if (!empty($displayData['title'])) {
		$content = str_replace('><path', '><title>' . $displayData['title'] . '</title><path', $content);
	}

	$content = str_replace('<svg', '<svg id="dp-icon-' . $icon . '"', $content);
}
?>
<span class="dp-icon dp-icon_<?php echo $icon; ?>"><?php echo $content; ?></span>
