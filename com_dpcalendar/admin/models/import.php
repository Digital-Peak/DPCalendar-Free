<?php
/**
 * @package   DPCalendar
 * @author    Digital Peak http://www.digital-peak.com
 * @copyright Copyright (C) 2007 - 2020 Digital Peak. All rights reserved.
 * @license   http://www.gnu.org/licenses/gpl.html GNU/GPL
 */
defined('_JEXEC') or die();

use DPCalendar\Helper\DPCalendarHelper;
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

		$calendarsToimport = $input->get('calendar', []);
		$existingCalendars = JModelLegacy::getInstance('Categories', 'CategoriesModel')->getItems();
		$start             = DPCalendarHelper::getDate($input->getCmd('filter_search_start', null));
		$end               = DPCalendarHelper::getDate($input->getCmd('filter_search_end', null));

		$msgs = [];
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
				$data                = [];
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
					$event->locations = [];
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
				$table->load(['xreference' => $xreference]);
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
		// The folder with the data
		$geoDBDirectory = \JFactory::getApplication()->get('tmp_path') . '/DPCalendar-Geodb';

		// Only update when we are not in free mode
		if (DPCalendarHelper::isFree()) {
			return;
		}

		// Fetch the content
		$content = DPCalendarHelper::fetchContent('https://software77.net/geo-ip/?DL=1');
		if (empty($content)) {
			throw new \Exception("Can't download the geolocation database from software77.net. Is the site blocked through a firewall?");
		}

		if ($content instanceof Exception) {
			throw $content;
		}

		// Sometimes you get a rate limit exceeded
		if (stristr($content, 'Rate limited exceeded') !== false) {
			throw new \Exception("You hit the rate limit of software77 to download the geo database, try again in 24 hours.");
		}

		// Ensure the directory exists
		if (!is_dir($geoDBDirectory)) {
			mkdir($geoDBDirectory);
		}

		// Store the downloaded file
		$ret = file_put_contents($geoDBDirectory . '/tmp.gz', $content);
		if ($ret === false) {
			throw new \Exception("Could not write the geolocation database to the temp folder. Are the permissions correct?");
		}

		// Free up some memory
		unset($content);

		// Decompress the file
		$uncompressed = '';

		// Create the zip reader
		$zp = @gzopen($geoDBDirectory . '/tmp.gz', 'rb');
		if ($zp === false) {
			@unlink($geoDBDirectory . '/tmp.gz');
			throw new \Exception("Can't uncompress the geolocation database file, there was a zip error.");
		}

		if ($zp !== false) {
			// Unzip the content
			while (!gzeof($zp)) {
				$uncompressed .= @gzread($zp, 102400);
			}

			// Close the zip reader
			@gzclose($zp);

			// Delete the zip file
			@unlink($geoDBDirectory . '/tmp.gz');
		}

		// Read the uncompressed content line by line
		$files = [];
		foreach (preg_split("/\r\n|\n|\r/", $uncompressed) as $line) {
			if (strpos($line, '#') === 0) {
				continue;
			}

			// Parse the line
			$data = explode(',', str_replace('"', '', $line));
			if (count($data) < 4) {
				continue;
			}

			// Filename contains the first part of the IP
			$fileName = current(explode('.', long2ip($data[0]))) . '.php';

			// Teh files array with the IP data file names
			$files[$fileName] = $geoDBDirectory . '/' . $fileName;

			// The buffer which contains the PHP code for the data array
			$buffer = '';

			// The files are PHP arrays for fast lookup
			if (!file_exists($geoDBDirectory . '/' . $fileName)) {
				// Create the main array
				$buffer .= '<?php' . PHP_EOL;
				$buffer .= 'return [' . PHP_EOL;
			}

			// The data array
			$buffer .= '[\'' . $data[0] . '\', \'' . $data[1] . '\', \'' . $data[4] . '\'],' . PHP_EOL;

			// Write the buffer
			file_put_contents($geoDBDirectory . '/' . $fileName, $buffer, FILE_APPEND | LOCK_EX);
		}

		// Close the main array
		foreach ($files as $file) {
			file_put_contents($file, '];', FILE_APPEND | LOCK_EX);
		}
	}

	public function getTable($type = 'Location', $prefix = 'DPCalendarTable', $config = [])
	{
		return parent::getTable($type, $prefix, $config);
	}
}
