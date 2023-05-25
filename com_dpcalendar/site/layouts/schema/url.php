<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2017 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */
defined('_JEXEC') or die();

// Compile the url
$url = JUri::getInstance()->toString(['scheme', 'host', 'port']) . '/';
$url .= trim($displayData['router']->getEventRoute($displayData['event']->id, $displayData['event']->catid), '/');
?>
<meta itemprop="url" content="<?php echo $url; ?>">
