<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2018 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

\defined('_JEXEC') or die();

use DigitalPeak\Component\DPCalendar\Administrator\HTML\Block\Icon;
use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Factory;

$app  = Factory::getApplication();
if (!$app instanceof CMSApplication) {
	return '';
}

$icon = basename((string) $displayData['icon']);
$path = JPATH_ROOT . '/templates/' . $app->getTemplate() . '/images/com_dpcalendar/icons/' . $icon . '.svg';
if (!file_exists($path)) {
	$path = JPATH_ROOT . '/templates/' . $app->getTemplate() . '/images/icons/' . $icon . '.svg';
}

if (!file_exists($path)) {
	$path = JPATH_ROOT . '/media/com_dpcalendar/images/icons/' . $icon . '.svg';
}

if (!file_exists($path)) {
	echo '';
	return;
}

if (!empty($displayData['raw'])) {
	echo @file_get_contents($path);
	return;
}

if (array_key_exists($path, Icon::$pathCache) && (!array_key_exists('force', $displayData) || !$displayData['force'])) {
	$content = '<svg><use href="#dp-icon-' . $icon . '"/></svg>';
} else {
	Icon::$pathCache[$path] = $path;

	$content = @file_get_contents($path) ?: '';
	if (!empty($displayData['title'])) {
		$content = str_replace('><path', '><title>' . $displayData['title'] . '</title><path', $content);
	}

	$content = str_replace('<svg', '<svg id="dp-icon-' . $icon . '"', $content);
}
?>
<span class="dp-icon dp-icon_<?php echo $icon; ?>"><?php echo $content; ?></span>
