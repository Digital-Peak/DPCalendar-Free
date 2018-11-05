<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2018 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */
defined('_JEXEC') or die();

JFormHelper::loadFieldClass('list');

class JFormFieldDPMenuItems extends JFormFieldList
{
	public $type = 'DPMenuItems';

	public function getOptions()
	{
		$app = JFactory::getApplication();
		$id  = $app->input->getInt('id');

		if ($app->input->get('view') == 'extcalendar') {
			\JPluginHelper::importPlugin('dpcalendar');
			$tmp = $app->triggerEvent('onCalendarsFetch');
			if (!empty($tmp)) {
				foreach ($tmp as $calendars) {
					foreach ($calendars as $fetchedCalendar) {
						if (strpos($fetchedCalendar->id, '-' . $id) > 0) {
							$id = $fetchedCalendar->id;
						}
					}
				}
			}
		}

		$calendar = \DPCalendar\Helper\DPCalendarHelper::getCalendar($id);

		if (!$calendar) {
			return [];
		}

		$db    = JFactory::getDbo();
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
			if (strpos($item->link, 'view=calendar') === false
				&& strpos($item->link, 'view=map') === false
				&& strpos($item->link, 'view=list') === false) {
				continue;
			}

			$params = new \Joomla\Registry\Registry($item->params);

			$ids = [];
			foreach ($params->get('ids', []) as $calendarId) {
				$ids[] = $calendarId;

				$cal = DPCalendarHelper::getCalendar($calendarId);
				if (!$cal || !method_exists($cal, 'getChildren')) {
					continue;
				}

				foreach ($cal->getChildren(true) as $child) {
					$ids[] = $child->id;
				}
			}

			if (!in_array($id, $ids)) {
				continue;
			}

			$options[] = \JHtml::_('select.option', $item->id, $item->title);
		}

		return $options;
	}
}
