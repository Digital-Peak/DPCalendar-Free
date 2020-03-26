<?php
/**
 * @package   DPCalendar
 * @author    Digital Peak http://www.digital-peak.com
 * @copyright Copyright (C) 2007 - 2020 Digital Peak. All rights reserved.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */
defined('_JEXEC') or die();

class PlgUserDPCalendar extends JPlugin
{
	protected $autoloadLanguage = true;

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

		// If the booking was added as guest user and now he registered, assign the booking to the attendee
		if ($isNew) {
			JLoader::import('components.com_dpcalendar.helpers.dpcalendar', JPATH_ADMINISTRATOR);

			JModelLegacy::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_dpcalendar/models');
			$model = JModelLegacy::getInstance('Booking', 'DPCalendarModel', ['ignore_request' => true]);
			if ($model && $booking = $model->assign($user)) {
				$loginUrl = JRoute::_(
					'index.php?option=com_users&view=login&return=' . base64_encode(DPCalendarHelperRoute::getBookingRoute($booking))
				);
				JFactory::getApplication()->enqueueMessage(JText::sprintf(
					'PLG_USER_DPCALENDAR_BOOKING_ASSIGNED',
					$booking->uid,
					$loginUrl
				));
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

	public function onContentPrepareForm($form, $data)
	{
		// Do nothing when disabled
		if (!$this->params->get('add_dpcalendar_user_fields', 1)) {
			return true;
		}

		if ($form->getName() != 'com_users.profile'
			&& !(JFactory::getApplication()->isClient('administrator') && $form->getName() == 'com_users.user')) {
			return true;
		}

		$form->loadFile(JPATH_PLUGINS . '/user/dpcalendar/forms/user.xml');

		return true;
	}
}
