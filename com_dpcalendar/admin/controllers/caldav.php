<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2018 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */
defined('_JEXEC') or die();

class DPCalendarControllerCaldav extends JControllerLegacy
{

	public function sync()
	{
		$db = JFactory::getDbo();

		// Sync users
		$db->setQuery('delete from #__dpcalendar_caldav_principals where external_id not in (select id from #__users)');
		$db->execute();
		$db->setQuery(
			'insert into #__dpcalendar_caldav_principals
				(uri, email, displayname, external_id) select concat("principals/", username) as uri, email, name as displayname, id
				from #__users u ON DUPLICATE KEY UPDATE email=u.email, displayname=u.name');
		$db->execute();

		$db->setQuery(
			'insert into #__dpcalendar_caldav_principals
				(uri, email, displayname, external_id) select concat("principals/", username, "/calendar-proxy-read") as uri, email, name as displayname, id
				from #__users u ON DUPLICATE KEY UPDATE email=u.email, displayname=u.name');
		$db->execute();
		$db->setQuery(
			'insert into #__dpcalendar_caldav_principals
				(uri, email, displayname, external_id) select concat("principals/", username, "/calendar-proxy-write") as uri, email, name as displayname, id
				from #__users u ON DUPLICATE KEY UPDATE email=u.email, displayname=u.name');
		$db->execute();

		// Sync calendars
		$db->setQuery(
			'delete p.*, c.*, cal.*, e.* from #__dpcalendar_caldav_principals p
				inner join #__dpcalendar_caldav_calendarinstances c on c.principaluri = p.uri
				inner join #__dpcalendar_caldav_calendars cal on cal.id = c.calendarid
				inner join #__dpcalendar_caldav_calendarobjects e on e.calendarid = c.id
				where p.external_id not in (select id from #__users)');
		$db->execute();

		$db->setQuery('select count(id) from #__users');
		$msg = sprintf(JText::_('COM_DPCALENDAR_CONTROLLER_CALDAV_SYNC_SUCCESS'), $db->loadResult());

		$this->setRedirect(JRoute::_('index.php?option=com_dpcalendar&view=tools', false), $msg);

		return true;
	}
}
