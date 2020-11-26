<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2017 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */
defined('_JEXEC') or die();
?>
<meta itemprop="startDate"
	  content="<?php echo $displayData['dateHelper']->getDate($displayData['event']->start_date, $displayData['event']->all_day)->format('c'); ?>"
>
<meta itemprop="endDate"
	  content="<?php echo $displayData['dateHelper']->getDate($displayData['event']->end_date, $displayData['event']->all_day)->format('c'); ?>"
>
