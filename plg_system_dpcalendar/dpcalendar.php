<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2017 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */
defined('_JEXEC') or die();

JLoader::import('components.com_dpcalendar.helpers.dpcalendar', JPATH_ADMINISTRATOR);

class PlgSystemDpcalendar extends \DPCalendar\Plugin\CalDAVPlugin
{

	protected $identifier = 'cd';

	public function onUserAfterSave($user, $isNew, $success, $msg)
	{
		if (!$success) {
			return;
		}

		$db = JFactory::getDbo();
		if ($isNew) {
			$db->setQuery(
				"insert into #__dpcalendar_caldav_principals (uri, email, displayname, external_id) values (" .
				$db->quote('principals/' . $user['username']) . ", " . $db->quote($user['email']) . ", " . $db->quote($user['name']) .
				", " . (int)$user['id'] . ')');
			$db->execute();
			$db->setQuery(
				"insert into #__dpcalendar_caldav_principals (uri, email, displayname, external_id) values (" .
				$db->quote('principals/' . $user['username'] . '/calendar-proxy-read') . ", " . $db->quote($user['email']) . ", " .
				$db->quote($user['name']) . ", " . (int)$user['id'] . ')');
			$db->execute();
			$db->setQuery(
				"insert into #__dpcalendar_caldav_principals (uri, email, displayname, external_id) values (" .
				$db->quote('principals/' . $user['username'] . '/calendar-proxy-write') . ", " . $db->quote($user['email']) . ", " .
				$db->quote($user['name']) . ", " . (int)$user['id'] . ')');
			$db->execute();
		} else {
			$db->setQuery(
				"update #__dpcalendar_caldav_principals set email=" . $db->quote($user['email']) . ", displayname=" . $db->quote($user['name']) .
				" where external_id=" . (int)$user['id']);
			$db->execute();
		}

		// If the booking was added as guest user and now he registered, assign
		// the booking to him
		if ($isNew) {
			JModelLegacy::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_dpcalendar/models');
			$model = JModelLegacy::getInstance('Booking', 'DPCalendarModel', array('ignore_request' => true));
			if ($model && $booking = $model->assign($user)) {
				$loginUrl = JRoute::_(
					'index.php?option=com_users&view=login&return=' . base64_encode(DPCalendarHelperRoute::getBookingRoute($booking)));
				JFactory::getApplication()->enqueueMessage(JText::sprintf('PLG_SYSTEM_DPCALENDAR_BOOKING_ASSIGNED',
					$booking->uid, $loginUrl));
			}
		}
	}

	public function onUserAfterDelete($user, $success, $msg)
	{
		if (!$success) {
			return;
		}

		// Delete membership
		$db = JFactory::getDbo();
		$db->setQuery(
			'delete from #__dpcalendar_caldav_groupmembers where
				principal_id in (select id from #__dpcalendar_caldav_principals where external_id = ' . (int)$user['id'] . ') or
				member_id in (select id from #__dpcalendar_caldav_principals where external_id = ' . (int)$user['id'] . ')');
		$db->execute();

		// Delete calendar data
		$db->setQuery(
			"delete from #__dpcalendar_caldav_calendarobjects where
				calendarid in (select id from #__dpcalendar_caldav_calendarinstances where principaluri = " . $db->quote('principals/' . $user['username']) . ")");
		$db->execute();


		// Delete calendars
		$db->setQuery(
			"delete from #__dpcalendar_caldav_calendars where
				id in (select calendarid from #__dpcalendar_caldav_calendarinstances where principaluri = " . $db->quote('principals/' . $user['username']) . ")");
		$db->execute();
		$db->setQuery("delete from #__dpcalendar_caldav_calendarinstances where principaluri = " . $db->quote('principals/' . $user['username']));
		$db->execute();

		// Delete principals
		$db->setQuery('delete from #__dpcalendar_caldav_principals where external_id = ' . (int)$user['id']);
		$db->execute();
	}

	public function prepareForm($eventId, $calendarId, $form, $data)
	{
		$form->removeField('alias');
		$form->removeField('state');
		$form->removeField('publish_up');
		$form->removeField('publish_down');
		$form->removeField('access');
		$form->removeField('featured');
		$form->removeField('access_content');
		$form->removeField('language');
		$form->removeField('location_ids');
		$form->removeField('metadesc');
		$form->removeField('metakey');
		$form->removeField('price');
		$form->removeField('earlybird');

		$form->setField(
			new SimpleXMLElement(
				'
				<field
				name="location"
				type="text"
				description="' . JText::_('PLG_SYSTEM_DPCALENDAR_DAV_FIELD_LOCATION_DESC') . '"
				translate_description="false"
				label="' . JText::_('PLG_SYSTEM_DPCALENDAR_DAV_FIELD_LOCATION_LABEL') . '"
				translate_label="false"
				size="40"
				filter="safeHtml"
				/>'));
		if (isset($data->locations)) {
			$form->setValue('location', null, \DPCalendar\Helper\Location::format($data->locations));
		}

		return true;
	}

	protected function createCalDAVEvent($uid, $icalData, $calendarId)
	{
		return $this->getBackend()->createCalendarObject(array($calendarId), $uid . '.ics', $icalData);
	}

	protected function updateCalDAVEvent($uid, $icalData, $calendarId)
	{
		return $this->getBackend()->updateCalendarObject(array($calendarId), $uid . '.ics', $icalData);
	}

	protected function deleteCalDAVEvent($uid, $calendarId)
	{
		return $this->getBackend()->deleteCalendarObject(array($calendarId), $uid . '.ics');
	}

	protected function getOriginalData($uid, $calendarId)
	{
		$db = JFactory::getDbo();
		$db->setQuery("select * from #__dpcalendar_caldav_calendarobjects where uri = " . $db->quote($db->escape($uid) . '.ics'));
		$row = $db->loadObject();

		return $row->calendardata;
	}

	protected function getContent($calendarId, JDate $startDate = null, JDate $endDate = null, JRegistry $options)
	{
		$db   = JFactory::getDbo();
		$user = JFactory::getUser();

		$join = $db->getQuery(true);
		$join->select("distinct REPLACE( REPLACE( linkp.uri,  '/calendar-proxy-write',  '' ) ,  '/calendar-proxy-read',  '' )");
		$join->from($db->quoteName('#__dpcalendar_caldav_principals') . ' memberp');
		$join->join('inner', "#__dpcalendar_caldav_groupmembers m ON memberp.id = m.member_id");
		$join->join('inner', "#__dpcalendar_caldav_principals linkp ON m.principal_id = linkp.id");
		$join->where('memberp.external_id = ' . (int)$user->id);

		$query = $db->getQuery(true);

		$query->select('e.*');
		$query->from($db->quoteName('#__dpcalendar_caldav_calendarobjects') . ' AS e');
		$query->join('RIGHT', '#__dpcalendar_caldav_calendarinstances c on c.calendarid = e.calendarid');
		$query->where('(c.principaluri = ' . $db->quote('principals/' . $user->username) . ' or c.principaluri in (' . $join . '))');
		$query->where('c.id = ' . (int)$calendarId);

		if ($startDate != null) {
			$start         = $startDate->format('U');
			$dateCondition = 'e.firstoccurence  >= ' . $start;
			if ($endDate !== null) {
				$end           = $endDate->format('U');
				$dateCondition = '(e.lastoccurence between ' . $start . ' and ' . $end . ' or e.firstoccurence between ' . $start . ' and ' . $end .
					')';
			}
		}

		$query->order('firstoccurence asc');

		$db->setQuery($query);
		$rows = $db->loadObjectList();
		if (empty($rows)) {
			return array();
		}

		$text   = array();
		$text[] = 'BEGIN:VCALENDAR';
		foreach ($rows as $item) {
			if (empty($item->calendardata)) {
				continue;
			}
			$start = strpos($item->calendardata, 'BEGIN:VEVENT');
			$end   = strrpos($item->calendardata, 'END:VEVENT');
			$tmp   = substr($item->calendardata, $start, $end - $start + 10);

			if (strpos($tmp, 'ORGANIZER:') === false) {
				$tmp = str_replace('END:VEVENT', 'ORGANIZER:' . $user->name . PHP_EOL . 'END:VEVENT', $tmp);
			}

			$text = array_merge($text, explode(PHP_EOL, $tmp));
		}

		$text[] = 'END:VCALENDAR';

		// Echo '<pre>';print_R($text);echo '</pre>';die;
		return $text;
	}

	public function fetchCalendars($calendarIds = null)
	{
		JModelLegacy::addIncludePath(JPATH_SITE . '/components/com_dpcalendar/models', 'DPCalendarModel');
		$model = JModelLegacy::getInstance('Profile', 'DPCalendarModel');

		$data  = array();
		$items = $model->getItems();
		if (empty($items)) {
			return $data;
		}
		foreach ($items as $caldavCalendar) {
			$calendar            = $this->createCalendar($caldavCalendar->id, $caldavCalendar->displayname,
				$caldavCalendar->description,
				$caldavCalendar->calendarcolor);
			$calendar->canCreate = $caldavCalendar->canEdit;
			$calendar->canEdit   = $caldavCalendar->canEdit;
			$calendar->canDelete = $caldavCalendar->canEdit;
			$data[]              = $calendar;
		}

		return $data;
	}

	private function getBackend()
	{
		$config = JFactory::getConfig();

		JLoader::import('components.com_dpcalendar.libraries.vendor.autoload', JPATH_ADMINISTRATOR);

		$pdo = new \PDO('mysql:host=' . $config->get('host') . ';dbname=' . $config->get('db'), $config->get('user'),
			$config->get('password'));
		$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

		$calendarBackend                                 = new \DPCalendar\Sabre\CalDAV\Backend\DPCalendar($pdo);
		$calendarBackend->calendarTableName              = $config->get('dbprefix') . 'dpcalendar_caldav_calendars';
		$calendarBackend->calendarObjectTableName        = $config->get('dbprefix') . 'dpcalendar_caldav_calendarobjects';
		$calendarBackend->calendarChangesTableName       = $config->get('dbprefix') . 'dpcalendar_caldav_calendarchanges';
		$calendarBackend->calendarInstancesTableName     = $config->get('dbprefix') . 'dpcalendar_caldav_calendarinstances';
		$calendarBackend->calendarSubscriptionsTableName = $config->get('dbprefix') . 'dpcalendar_caldav_calendarsubscriptions';

		return $calendarBackend;
	}

	public function onInstallerBeforePackageDownload(&$url, &$headers)
	{
		$uri = JUri::getInstance($url);

		// Note: as the Download ID is common for all extensions, this plugin
		// will be triggered for all
		// extensions with a download URL on our site
		$host = $uri->getHost();
		if ($host !== 'joomla.digital-peak.com') {
			return true;
		}

		// Get the download ID
		JLoader::import('joomla.application.component.helper');
		$component = JComponentHelper::getComponent('com_dpcalendar');

		$dlid = trim($component->params->get('downloadid', ''));

		// If the download ID is invalid, return without any further action
		if (!preg_match('/^([0-9]{1,}:)?[0-9a-f]{32}$/i', $dlid)) {
			return true;
		}

		// Appent the Download ID to the download URL
		if (!empty($dlid)) {
			$uri->setVar('dlid', $dlid);
			$url = $uri->toString();
		}

		return true;
	}
}
