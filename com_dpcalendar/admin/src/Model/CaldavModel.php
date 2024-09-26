<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2024 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Component\DPCalendar\Administrator\Model;

\defined('_JEXEC') or die();

use Joomla\CMS\MVC\Model\BaseDatabaseModel;

class CaldavModel extends BaseDatabaseModel
{
	public function syncUsers(): int
	{
		$db = $this->getDatabase();

		// Sync users
		$db->setQuery('delete from #__dpcalendar_caldav_principals where external_id not in (select id from #__users)');
		$db->execute();
		$db->setQuery(
			'insert into #__dpcalendar_caldav_principals
				(uri, email, displayname, external_id) select concat("principals/", username) as uri, email, name as displayname, id
				from #__users u ON DUPLICATE KEY UPDATE email=u.email, displayname=u.name'
		);
		$db->execute();

		$db->setQuery(
			'insert into #__dpcalendar_caldav_principals
				(uri, email, displayname, external_id) select concat("principals/", username, "/calendar-proxy-read") as uri, email, name as displayname, id
				from #__users u ON DUPLICATE KEY UPDATE email=u.email, displayname=u.name'
		);
		$db->execute();
		$db->setQuery(
			'insert into #__dpcalendar_caldav_principals
				(uri, email, displayname, external_id) select concat("principals/", username, "/calendar-proxy-write") as uri, email, name as displayname, id
				from #__users u ON DUPLICATE KEY UPDATE email=u.email, displayname=u.name'
		);
		$db->execute();

		// Sync calendars
		$db->setQuery(
			'delete p.*, c.*, cal.*, e.* from #__dpcalendar_caldav_principals p
				inner join #__dpcalendar_caldav_calendarinstances c on c.principaluri = p.uri
				inner join #__dpcalendar_caldav_calendars cal on cal.id = c.calendarid
				inner join #__dpcalendar_caldav_calendarobjects e on e.calendarid = c.id
				where p.external_id not in (select id from #__users)'
		);
		$db->execute();

		$db->setQuery('select count(id) from #__users');

		return $db->loadResult() ?: 0;
	}
}
