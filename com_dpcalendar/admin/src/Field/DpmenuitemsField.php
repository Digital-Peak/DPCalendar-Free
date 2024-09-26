<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2018 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Component\DPCalendar\Administrator\Field;

defined('_JEXEC') or die();

use DigitalPeak\Component\DPCalendar\Administrator\Calendar\CalendarInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Field\ListField;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Registry\Registry;

class DpmenuItemsField extends ListField
{
	public $type = 'DPMenuItems';

	protected function getOptions()
	{
		$app = Factory::getApplication();
		$id  = $app->getInput()->getInt('id', 0);

		if ($app->getInput()->get('view') == 'extcalendar') {
			PluginHelper::importPlugin('dpcalendar');
			$tmp = $app->triggerEvent('onCalendarsFetch');
			if (!empty($tmp)) {
				foreach ($tmp as $calendars) {
					foreach ($calendars as $fetchedCalendar) {
						if (strpos((string)$fetchedCalendar->id, '-' . $id) > 0) {
							$id = $fetchedCalendar->id;
						}
					}
				}
			}
		}

		$calendar = Factory::getApplication()->bootComponent('dpcalendar')->getMVCFactory()->createModel('Calendar', 'Administrator')->getCalendar($id);

		if (!$calendar instanceof CalendarInterface) {
			return [];
		}

		$db    = $this->getDatabase();
		$query = $db->getQuery(true);

		// Prepare the query.
		$query->select('m.*')
			->from('#__menu AS m')
			->where('m.client_id = 0')
			->where('m.id > 1');
		$query->where('m.published = 1');

		$query->select('e.element')
			->join('LEFT', '#__extensions AS e ON m.component_id = e.extension_id')
			->where('(e.enabled = 1 OR e.enabled IS NULL)');
		$query->where("e.element = 'com_dpcalendar'");

		$query->order('m.lft');

		$db->setQuery($query);

		$options = parent::getOptions();
		foreach ($db->loadObjectList() as $item) {
			if (!str_contains((string)$item->link, 'view=calendar')
				&& !str_contains((string)$item->link, 'view=map')
				&& !str_contains((string)$item->link, 'view=list')) {
				continue;
			}

			$params = new Registry($item->params);

			$ids = [];
			foreach ($params->get('ids', []) as $calendarId) {
				$ids[] = $calendarId;

				$cal = Factory::getApplication()->bootComponent('dpcalendar')->getMVCFactory()->createModel('Calendar', 'Administrator')->getCalendar($calendarId);
				if (!$cal instanceof CalendarInterface) {
					continue;
				}

				foreach ($cal->getChildren(true) as $child) {
					$ids[] = $child->getId();
				}
			}

			if (!in_array($id, $ids)) {
				continue;
			}

			$options[] = HTMLHelper::_('select.option', $item->id, $item->title);
		}

		return $options;
	}
}
