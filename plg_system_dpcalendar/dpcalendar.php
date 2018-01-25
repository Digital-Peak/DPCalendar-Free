<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2018 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */
defined('_JEXEC') or die();

if (!JLoader::import('components.com_dpcalendar.helpers.dpcalendar', JPATH_ADMINISTRATOR)) {
	return;
}

JLoader::registerAlias('DPCalendarHelperLocation', '\\DPCalendar\\Helper\\Location', '6.0');
JLoader::registerAlias('DPCalendarHelperBooking', '\\DPCalendar\\Helper\\Booking', '6.0');

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
			$query = $db->getQuery(true);
			$query->insert('#__dpcalendar_caldav_principals');
			$query->set('uri = ' . $db->quote('principals/' . $user['username']));
			$query->set('email = ' . $db->quote($user['email']));
			$query->set('displayname = ' . $db->quote($user['name']));
			$query->set('external_id = ' . (int)$user['id']);
			$db->setQuery($query);
			$db->execute();

			$query = $db->getQuery(true);
			$query->insert('#__dpcalendar_caldav_principals');
			$query->set('uri = ' . $db->quote('principals/' . $user['username'] . '/calendar-proxy-read'));
			$query->set('email = ' . $db->quote($user['email']));
			$query->set('displayname = ' . $db->quote($user['name']));
			$query->set('external_id = ' . (int)$user['id']);
			$db->setQuery($query);
			$db->execute();

			$query = $db->getQuery(true);
			$query->insert('#__dpcalendar_caldav_principals');
			$query->set('uri = ' . $db->quote('principals/' . $user['username'] . '/calendar-proxy-write'));
			$query->set('email = ' . $db->quote($user['email']));
			$query->set('displayname = ' . $db->quote($user['name']));
			$query->set('external_id = ' . (int)$user['id']);
			$db->setQuery($query);
			$db->execute();
		} else {
			$query = $db->getQuery(true);
			$query->update('#__dpcalendar_caldav_principals');
			$query->set('uri = ' . $db->quote('principals/' . $user['username']));
			$query->set('email = ' . $db->quote($user['email']));
			$query->set('displayname = ' . $db->quote($user['name']));
			$query->where('external_id = ' . (int)$user['id']);
			$query->where('uri not like ' . $db->quote('principals/%/calendar-proxy-read'));
			$query->where('uri not like ' . $db->quote('principals/%/calendar-proxy-write'));
			$db->setQuery($query);
			$db->execute();

			$query = $db->getQuery(true);
			$query->update('#__dpcalendar_caldav_principals');
			$query->set('uri = ' . $db->quote('principals/' . $user['username'] . '/calendar-proxy-read'));
			$query->set('email = ' . $db->quote($user['email']));
			$query->set('displayname = ' . $db->quote($user['name']));
			$query->where('external_id = ' . (int)$user['id']);
			$query->where('uri like ' . $db->quote('principals/%/calendar-proxy-read'));
			$db->setQuery($query);
			$db->execute();

			$query = $db->getQuery(true);
			$query->update('#__dpcalendar_caldav_principals');
			$query->set('uri = ' . $db->quote('principals/' . $user['username'] . '/calendar-proxy-write'));
			$query->set('email = ' . $db->quote($user['email']));
			$query->set('displayname = ' . $db->quote($user['name']));
			$query->where('external_id = ' . (int)$user['id']);
			$query->where('uri like ' . $db->quote('principals/%/calendar-proxy-write'));
			$db->setQuery($query);
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

		$db = JFactory::getDbo();

		// Delete membership
		$query = $db->getQuery(true);
		$query->delete('#__dpcalendar_caldav_groupmembers');
		$query->where('principal_id in (select id from #__dpcalendar_caldav_principals where external_id = ' . (int)$user['id'] . ')');
		$query->orWhere('member_id in (select id from #__dpcalendar_caldav_principals where external_id = ' . (int)$user['id'] . ')');
		$db->setQuery($query);
		$db->execute();

		// Delete calendar data
		$subQuery = $db->getQuery(true);
		$subQuery->select('id');
		$subQuery->from('#__dpcalendar_caldav_calendarinstances');
		$subQuery->where('principaluri = ' . $db->quote('principals/' . $user['username']));

		$query = $db->getQuery(true);
		$query->delete('#__dpcalendar_caldav_calendarobjects');
		$query->where('calendarid in (' . $subQuery . ')');
		$db->setQuery($query);
		$db->execute();

		// Delete calendars
		$subQuery = $db->getQuery(true);
		$subQuery->select('calendarid');
		$subQuery->from('#__dpcalendar_caldav_calendarinstances');
		$subQuery->where('principaluri = ' . $db->quote('principals/' . $user['username']));

		$query = $db->getQuery(true);
		$query->delete('#__dpcalendar_caldav_calendars');
		$query->where('id in (' . $subQuery . ')');
		$db->setQuery($query);
		$db->execute();

		$query = $db->getQuery(true);
		$query->delete('#__dpcalendar_caldav_calendarinstances');
		$query->where('principaluri = ' . $db->quote('principals/' . $user['username']));
		$db->setQuery($query);
		$db->execute();

		// Delete principals
		$query = $db->getQuery(true);
		$query->delete('#__dpcalendar_caldav_principals');
		$query->where('external_id = ' . (int)$user['id']);
		$db->setQuery($query);
		$db->execute();
	}

	public function onContentAfterDelete($context, $item)
	{
		// Check if it is a category to delete
		if ($context != 'com_categories.category') {
			return;
		}

		// Check if the category belongs to DPCalendar
		if ($item->extension != 'com_dpcalendar') {
			return;
		}

		// Add the required table and module path
		JTable::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_dpcalendar/tables');
		JModelLegacy::addIncludePath(JPATH_SITE . '/components/com_dpcalendar/models');

		// Load the model
		$model = JModelLegacy::getInstance('Form', 'DPCalendarModel', array('ignore_request' => true));

		// Select all events which do belong to the category
		$db    = JFactory::getDbo();
		$query = $db->getQuery(true);
		$query->select('id')->from('#__dpcalendar_events')->where('original_id in (0, -1) and catid=' . (int)$item->id);
		$db->setQuery($query);

		// Loop over the events
		foreach ($db->loadAssocList() as $eventId) {
			// We are using here the model to properly trigger the events

			// Unpublish it first
			$model->publish($eventId, -2);

			// The actually delete the event
			if (!$model->delete($eventId)) {
				// Add the error message
				JFactory::getApplication()->enqueueMessage($model->getError(), 'error');
			}
		}
	}

	public function onContentPrepareForm($form, $data)
	{
		if ($form->getName() == 'com_users.profile') {

			// Load the language
			JFactory::getLanguage()->load('com_dpcalendar', JPATH_ADMINISTRATOR . '/components/com_dpcalendar');

			$form->loadFile(JPATH_PLUGINS . '/system/dpcalendar/forms/user.xml');
		}

		return parent::onContentPrepareForm($form, $data);
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
		$query->join('RIGHT', '#__dpcalendar_caldav_calendarinstances c on c.id = e.calendarid');
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
			$calendar = $this->createCalendar(
				$caldavCalendar->id,
				$caldavCalendar->displayname,
				$caldavCalendar->description,
				$caldavCalendar->calendarcolor
			);

			if (!empty($calendarIds) && !in_array($caldavCalendar->id, $calendarIds)) {
				continue;
			}

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
