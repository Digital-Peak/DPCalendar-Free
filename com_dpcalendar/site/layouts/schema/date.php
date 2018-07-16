<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2018 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */
defined('_JEXEC') or die();
?>
<meta itemprop="startDate"
      content="<?php echo $displayData['dateHelper']->getDate($displayData['event']->start_date, $displayData['event']->all_day)->format('c'); ?>"
>
<meta itemprop="endDate"
      content="<?php echo $displayData['dateHelper']->getDate($displayData['event']->end_date, $displayData['event']->all_day)->format('c'); ?>"
>
