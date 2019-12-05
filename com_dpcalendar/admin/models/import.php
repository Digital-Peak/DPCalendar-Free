<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2019 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */

defined('_JEXEC') or die();

use DPCalendar\Helper\DPCalendarHelper;
use GeoIp2\Database\Reader;
use Joomla\Registry\Registry;

JLoader::import('joomla.application.component.modellist');

class DPCalendarModelImport extends JModelLegacy
{
	public function import()
	{
		JPluginHelper::importPlugin('dpcalendar');
		JModelLegacy::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_categories/models');
		JModelLegacy::addTablePath(JPATH_ADMINISTRATOR . '/components/com_categories/tables');

		$input = JFactory::getApplication()->input;

		$input->set('extension', 'com_dpcalendar');
		$input->post->set('extension', 'com_dpcalendar');

		$tmp       = JFactory::getApplication()->triggerEvent('onCalendarsFetch');
		$calendars = call_user_func_array('array_merge', (array)$tmp);

		$calendarsToimport = $input->get('calendar', array());
		$existingCalendars = JModelLegacy::getInstance('Categories', 'CategoriesModel')->getItems();
		$start             = DPCalendarHelper::getDate($input->getCmd('filter_search_start', null));
		$end               = DPCalendarHelper::getDate($input->getCmd('filter_search_end', null));

		$msgs = array();
		foreach ($calendars as $cal) {
			if (!in_array($cal->id, $calendarsToimport)) {
				continue;
			}

			$category = array_filter(
				$existingCalendars,
				function ($e) use ($cal) {
					return $e->title == $cal->title;
				}
			);

			if (is_array($category)) {
				$category = reset($category);
			}

			if ($category == null) {
				$data                = array();
				$data['id']          = 0;
				$data['title']       = $cal->title;
				$data['description'] = $cal->description;
				$data['extension']   = 'com_dpcalendar';
				$data['parent_id']   = 1;
				$data['published']   = 1;
				$data['language']    = '*';

				$model = JModelLegacy::getInstance('Category', 'CategoriesModel');
				$model->save($data);
				$category = $model->getItem($model->getState('category.id'));
			}

			$events = JFactory::getApplication()->triggerEvent('onEventsFetch', [$cal->id, $start, $end, new Registry(['expand' => false])]);
			$events = call_user_func_array('array_merge', (array)$events);

			$counter        = 0;
			$counterUpdated = 0;
			$filter         = strtolower($input->get('filter_search', ''));
			foreach ($events as $event) {
				$text = strtolower($event->title . ' ' . $event->description . ' ' . $event->url);
				if (!empty($filter) && strpos($text, $filter) === false) {
					continue;
				}

				$eventData = (array)$event;

				if (!isset($event->locations)) {
					$event->locations = array();
				}

				$eventData['location_ids'] = array_map(function ($l) {
					return $l->id;
				}, $event->locations);

				// Setting the reference to the old event
				$xreference              = $eventData['id'];
				$eventData['xreference'] = $xreference;

				unset($eventData['id']);
				unset($eventData['locations']);
				$eventData['alias'] = !empty($event->alias) ? $event->alias : JApplicationHelper::stringURLSafe($event->title);
				$eventData['catid'] = $category->id;

				// Find an existing event with the same xreference
				$table = JTable::getInstance('Event', 'DPCalendarTable');
				$table->load(array('xreference' => $xreference));
				if ($table->id) {
					$eventData['id']          = $table->id;
					$eventData['original_id'] = $table->original_id;
				}

				JModelLegacy::addIncludePath(JPATH_SITE . '/components/com_dpcalendar/models', 'DPCalendarModel');
				$model = JModelLegacy::getInstance('Form', 'DPCalendarModel');
				$model->getState();

				if (!$model->save($eventData)) {
					JFactory::getApplication()->enqueueMessage($model->getError(), 'warning');
					continue;
				}

				!empty($eventData['id']) ? $counterUpdated++ : $counter++;
			}
			$msgs[] = sprintf(JText::_('COM_DPCALENDAR_N_ITEMS_CREATED'), $counter, $cal->title);
			$msgs[] = sprintf(JText::_('COM_DPCALENDAR_N_ITEMS_UPDATED'), $counterUpdated, $cal->title);
		}
		$this->set('messages', $msgs);
	}

	public function importGeoDB()
	{
		$geoDBFile = \JFactory::getApplication()->get('tmp_path') . '/GeoLite2-Country.mmdb';

		// Don't update when the file was fetched 10 days ago
		if (file_exists($geoDBFile) && (time() - filemtime($geoDBFile) < (60 * 60 * 24 * 10))) {
			return;
		}

		// Only update when we are in free mode
		if (file_exists($geoDBFile) && DPCalendarHelper::isFree()) {
			return;
		}

		$content = DPCalendarHelper::fetchContent('http://geolite.maxmind.com/download/geoip/database/GeoLite2-Country.mmdb.gz');

		if (empty($content)) {
			throw new \Exception("Can't download the geolocation database from maxmind. Is the site blocked through a firewall?");
		}

		if ($content instanceof Exception) {
			throw $content;
		}

		// Sometimes you get a rate limit exceeded
		if (stristr($content, 'Rate limited exceeded') !== false) {
			throw new \Exception("You hit the rate limit of maxmind to download the geodatabase.");
		}

		$ret = file_put_contents($geoDBFile . '.gz', $content);
		if ($ret === false) {
			throw new \Exception("Could not write the geolocation database to the temp folder. Are the permissions correct?");
		}

		unset($content);

		// Decompress the file
		$uncompressed = '';

		$zp = @gzopen($geoDBFile . '.gz', 'rb');

		if ($zp === false) {
			throw new \Exception("Can't uncompress the geolocation database file, there was a zip error.");
		}

		if ($zp !== false) {
			while (!gzeof($zp)) {
				$uncompressed .= @gzread($zp, 102400);
			}

			@gzclose($zp);

			@unlink($geoDBFile . '.gz');
		}

		try {
			file_put_contents($geoDBFile, $uncompressed);
			new Reader($geoDBFile);
		} catch (\Exception $e) {
			unlink($geoDBFile);

			throw $e;
		}
	}

	public function getTable($type = 'Location', $prefix = 'DPCalendarTable', $config = array())
	{
		return parent::getTable($type, $prefix, $config);
	}
}
