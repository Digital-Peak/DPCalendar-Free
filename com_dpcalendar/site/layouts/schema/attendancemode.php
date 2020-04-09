<?php
/**
 * @package   DPCalendar
 * @author    Digital Peak http://www.digital-peak.com
 * @copyright Copyright (C) 2007 - 2020 Digital Peak. All rights reserved.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */
defined('_JEXEC') or die();

$mode = 'OnlineEventAttendanceMode';
if (isset($displayData['event']->location_ids) && $displayData['event']->location_ids) {
	$mode = 'OfflineEventAttendanceMode';
}
if (isset($displayData['event']->locations) && $displayData['event']->locations) {
	$mode = 'OfflineEventAttendanceMode';
}
?>
<meta itemprop="eventAttendanceMode" content="http://schema.org/<?php echo $mode; ?>">
