<?php

/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2018 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */
class jc_com_dpcalendar extends JCommentsPlugin
{

	function getObjectInfo ($id, $language = null)
	{
		$info = new JCommentsObjectInfo();

		$event = JTable::getInstance('Event', 'DPCalendarTable');
		$event->load($id);

		if (! empty($event->id))
		{
			$info->title = $event->title;
			$info->access = $event->access;
			$info->userid = $event->created_by;
			$info->link = DPCalendarHelperRoute::getEventRoute($event->id, $event->catid);
		}

		return $info;
	}
}
