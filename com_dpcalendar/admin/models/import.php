<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2014 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

defined('_JEXEC') or die();

use DPCalendar\Helper\DPCalendarHelper;
use DigitalPeak\ThinHTTP as HTTP;
use Joomla\CMS\Application\ApplicationHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Table\Table;
use Joomla\Registry\Registry;

class DPCalendarModelImport extends BaseDatabaseModel
{
	public function import()
	{
		PluginHelper::importPlugin('dpcalendar');
		BaseDatabaseModel::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_categories/models');
		BaseDatabaseModel::addTablePath(JPATH_ADMINISTRATOR . '/components/com_categories/tables');

		$input = Factory::getApplication()->input;

		$tmp       = Factory::getApplication()->triggerEvent('onCalendarsFetch');
		$calendars = call_user_func_array('array_merge', (array)$tmp);

		$categoriesModel = BaseDatabaseModel::getInstance('Categories', 'CategoriesModel', ['ignore_request' => true]);
		$categoriesModel->setState('filter.extension', 'com_dpcalendar');
		$categoriesModel->setState('filter.published', 'all');
		$existingCalendars = $categoriesModel->getItems();
		$calendarsToimport = $input->get('calendar', []);
		$start             = DPCalendarHelper::getDate($input->getCmd('filter_search_start', null));
		$end               = DPCalendarHelper::getDate($input->getCmd('filter_search_end', null));

		$msgs = [];
		foreach ($calendars as $cal) {
			if (!in_array($cal->id, $calendarsToimport)) {
				continue;
			}

			$category = array_filter($existingCalendars, fn ($e) => $e->title == $cal->title);

			if (is_array($category)) {
				$category = reset($category);
			}

			if (!$category) {
				$data                = [];
				$data['id']          = 0;
				$data['title']       = $cal->title;
				$data['description'] = $cal->description;
				$data['extension']   = 'com_dpcalendar';
				$data['parent_id']   = 1;
				$data['published']   = 1;
				$data['language']    = '*';

				$model = BaseDatabaseModel::getInstance('Category', 'CategoriesModel');
				$model->save($data);
				$category = $model->getItem($model->getState('category.id'));
			}

			$events = Factory::getApplication()->triggerEvent('onEventsFetch', [$cal->id, $start, $end, new Registry(['expand' => false])]);
			$events = call_user_func_array('array_merge', (array)$events);

			$startDateAsSQL = $start->toSql();
			$endDateAsSQL   = $end->toSql();
			$counter        = 0;
			$counterUpdated = 0;
			$filter         = strtolower($input->get('filter_search', ''));
			foreach ($events as $event) {
				$text = strtolower($event->title . ' ' . $event->description . ' ' . $event->url);
				if (!empty($filter) && strpos($text, $filter) === false) {
					continue;
				}

				// Check if the event is within the date range
				if (($event->start_date < $startDateAsSQL && $event->end_date < $startDateAsSQL)
					|| ($event->start_date > $startDateAsSQL && $event->end_date > $endDateAsSQL)) {
					continue;
				}

				$eventData = (array)$event;

				if (!isset($event->locations)) {
					$event->locations = [];
				}

				$eventData['location_ids'] = array_map(fn ($l) => $l->id, $event->locations);

				// Setting the reference to the old event
				$xreference              = $eventData['id'];
				$eventData['xreference'] = $xreference;

				unset($eventData['id']);
				unset($eventData['locations']);
				$eventData['alias'] = !empty($event->alias) ? $event->alias : ApplicationHelper::stringURLSafe($event->title);
				$eventData['catid'] = $category->id;

				// Find an existing event with the same xreference
				$table = Table::getInstance('Event', 'DPCalendarTable');
				$table->load(['xreference' => $xreference]);
				if ($table->id) {
					$eventData['id']          = $table->id;
					$eventData['original_id'] = $table->original_id;
				}

				BaseDatabaseModel::addIncludePath(JPATH_SITE . '/components/com_dpcalendar/models', 'DPCalendarModel');
				$model = BaseDatabaseModel::getInstance('Form', 'DPCalendarModel');
				$model->getState();

				if (!$model->save($eventData)) {
					Factory::getApplication()->enqueueMessage($model->getError(), 'warning');
					continue;
				}

				!empty($eventData['id']) ? $counterUpdated++ : $counter++;
			}
			$msgs[] = sprintf(Text::_('COM_DPCALENDAR_N_ITEMS_CREATED'), $counter, $cal->title);
			$msgs[] = sprintf(Text::_('COM_DPCALENDAR_N_ITEMS_UPDATED'), $counterUpdated, $cal->title);
		}
		$this->set('messages', $msgs);
	}

	public function importGeoDB()
	{
		// The folder with the data
		$geoDBDirectory = Factory::getApplication()->get('tmp_path') . '/DPCalendar-Geodb';

		// Only update when we are not in free mode
		if (DPCalendarHelper::isFree()) {
			return;
		}

		// Fetch the content
		$content = (new HTTP())->get('https://iptoasn.com/data/ip2country-v4-u32.tsv.gz')->dp->body;
		if (empty($content)) {
			throw new \Exception("Can't download the geolocation database from iptoasn.com. Is the site blocked through a firewall?");
		}

		if ($content instanceof Exception) {
			throw $content;
		}

		// Ensure the directory exists
		if (!is_dir($geoDBDirectory)) {
			mkdir($geoDBDirectory);
		}

		// Store the downloaded file
		$ret = file_put_contents($geoDBDirectory . '/tmp.gz', $content);
		if ($ret === false) {
			throw new \Exception('Could not write the geolocation database to the temp folder. Are the permissions correct?');
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
			$data = explode("\t", $line);
			if (count($data) < 3 || $data[2] === 'None') {
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
			$buffer .= '[\'' . $data[0] . '\', \'' . $data[1] . '\', \'' . $data[2] . '\'],' . PHP_EOL;

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
