<?php
/**
 * @package   DPCalendar
 * @author    Digital Peak http://www.digital-peak.com
 * @copyright Copyright (C) 2007 - 2020 Digital Peak. All rights reserved.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */
defined('_JEXEC') or die();

// Compile the url
$url = JUri::getInstance()->toString(['scheme', 'host', 'port']) . '/';
$url .= trim($displayData['router']->getEventRoute($displayData['event']->id, $displayData['event']->catid), '/');
?>
<meta itemprop="url" content="<?php echo $url; ?>">
