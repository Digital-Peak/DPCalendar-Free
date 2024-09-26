<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2020 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Plugin\User\DPCalendar\Extension;

\defined('_JEXEC') or die();

use DigitalPeak\Component\DPCalendar\Administrator\Model\BookingModel;
use DigitalPeak\Component\DPCalendar\Administrator\Table\BookingTable;
use DigitalPeak\Component\DPCalendar\Site\Helper\RouteHelper;
use Joomla\CMS\Application\CMSApplicationInterface;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Router\Route;
use Joomla\Database\DatabaseAwareTrait;

class DPCalendar extends CMSPlugin
{
	use DatabaseAwareTrait;

	protected $autoloadLanguage = true;

	public function onUserAfterSave(array $user, bool $isNew, bool $success): void
	{
		if (!$success) {
			return;
		}

		$app = $this->getApplication();
		if (!$app instanceof CMSApplicationInterface) {
			return;
		}

		$db = $this->getDatabase();
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
			$model = $app->bootComponent('dpcalendar')->getMVCFactory()->createModel('Booking', 'Administrator', ['ignore_request' => true]);
			if ($model instanceof BookingModel && ($booking = $model->assign($user)) instanceof BookingTable) {
				$loginUrl = Route::_(
					'index.php?option=com_users&view=login&return=' . base64_encode(RouteHelper::getBookingRoute((object)$booking->getData()))
				);
				$app->enqueueMessage(Text::sprintf('PLG_USER_DPCALENDAR_BOOKING_ASSIGNED', $booking->uid, $loginUrl));
			}
		}
	}

	public function onUserAfterDelete(array $user, bool $success): void
	{
		if (!$success) {
			return;
		}

		$db = $this->getDatabase();

		// Delete membership
		$query = $db->getQuery(true);
		$query->delete('#__dpcalendar_caldav_groupmembers');
		$query->where('(principal_id in (select id from #__dpcalendar_caldav_principals where external_id = ' . (int)$user['id'] . '))'
			. ' or (member_id in (select id from #__dpcalendar_caldav_principals where external_id = ' . (int)$user['id'] . '))');

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

	public function onContentPrepareForm(Form $form): bool
	{
		// Do nothing when disabled
		if (!$this->params->get('add_dpcalendar_user_fields', 1)) {
			return true;
		}

		$app = $this->getApplication();
		if (!$app instanceof CMSApplicationInterface) {
			return true;
		}

		if ($form->getName() !== 'com_users.profile' && $form->getName() !== 'com_admin.profile'
			&& !($app->isClient('administrator') && $form->getName() === 'com_users.user')) {
			return true;
		}

		$form->loadFile(JPATH_PLUGINS . '/user/dpcalendar/forms/user.xml');

		return true;
	}
}
