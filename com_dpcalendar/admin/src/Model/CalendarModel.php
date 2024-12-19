<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2024 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Component\DPCalendar\Administrator\Model;

\defined('_JEXEC') or die();

use DigitalPeak\Component\DPCalendar\Administrator\Calendar\CalendarInterface;
use DigitalPeak\Component\DPCalendar\Administrator\Calendar\InternalCalendar;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Registry\Registry;

class CalendarModel extends BaseDatabaseModel
{
	private static array $calendars = [];

	public function getCalendar(mixed $id): ?CalendarInterface
	{
		if (isset(self::$calendars[$id])) {
			return self::$calendars[$id];
		}

		$calendar = null;
		if (is_numeric($id) || $id == 'root') {
			$calendarNode = $this->bootComponent('dpcalendar')->getCategory()->get($id);
			if ($calendarNode == null) {
				return null;
			}

			$calendar = new InternalCalendar($calendarNode, $this->getCurrentUser());
		} else {
			PluginHelper::importPlugin('dpcalendar');
			$tmp = Factory::getApplication()->triggerEvent('onCalendarsFetch', [$id]);
			if (!empty($tmp)) {
				foreach ($tmp as $calendars) {
					foreach ($calendars as $fetchedCalendar) {
						$calendar = $fetchedCalendar;
					}
				}
			}
		}

		self::$calendars[$id] = $calendar;

		return $calendar;
	}

	public function increaseEtag(string $calendarId): void
	{
		// If we are not a native calendar do nothing
		if (!is_numeric($calendarId)) {
			return;
		}

		$calendar = self::getCalendar($calendarId);
		if (!$calendar instanceof CalendarInterface || ($calendar->getId() === '' || $calendar->getId() === '0')) {
			return;
		}

		$params = new Registry($calendar->getParams());
		$params->set('etag', $params->get('etag', 1) + 1);

		$db = $this->getDatabase();
		$db->setQuery('update #__categories set params = ' . $db->quote($params->toString()) . ' where id = ' . $db->quote($calendar->getId()));
		$db->execute();
	}
}
