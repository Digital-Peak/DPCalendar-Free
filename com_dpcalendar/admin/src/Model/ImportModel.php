<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2014 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Component\DPCalendar\Administrator\Model;

\defined('_JEXEC') or die();

use DigitalPeak\Component\DPCalendar\Administrator\Helper\DPCalendarHelper;
use DigitalPeak\ThinHTTP\CurlClient;
use Joomla\CMS\Application\ApplicationHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Registry\Registry;

class ImportModel extends BaseDatabaseModel
{
	public function import(): void
	{
		PluginHelper::importPlugin('dpcalendar');

		$app = Factory::getApplication();

		$input = $app->getInput();

		$tmp       = $app->triggerEvent('onCalendarsFetch');
		$calendars = array_merge(...(array)$tmp);

		$categoriesModel = $this->bootComponent('categories')->getMVCFactory()->createModel('Categories', 'Administrator', ['ignore_request' => true]);
		$categoriesModel->setState('filter.extension', 'com_dpcalendar');
		$categoriesModel->setState('filter.published', 'all');

		$existingCalendars = $categoriesModel->getItems();
		$calendarsToimport = $input->get('calendar', []);
		$start             = DPCalendarHelper::getDate($input->getCmd('filter_search_start', null));
		$end               = DPCalendarHelper::getDate($input->getCmd('filter_search_end', null));

		$msgs = [];
		foreach ($calendars as $cal) {
			if (!\in_array($cal->id, $calendarsToimport)) {
				continue;
			}

			$events = $app->triggerEvent('onEventsFetch', [$cal->id, $start, $end, new Registry(['expand' => false])]);
			$events = array_merge(...(array)$events);
			if ($events === []) {
				$msgs[] = \sprintf(Text::_('COM_DPCALENDAR_N_ITEMS_CREATED'), 0, $cal->title);
				continue;
			}

			$category = array_filter($existingCalendars, static fn ($e): bool => $e->title == $cal->title);

			if (\is_array($category)) {
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

				$model = $this->bootComponent('categories')->getMVCFactory()->createModel('Category', 'Administrator');
				$model->save($data);
				$category = $model->getItem($model->getState('category.id'));
			}

			$startDateAsSQL = $start->toSql();
			$endDateAsSQL   = $end->toSql();
			$counter        = 0;
			$counterUpdated = 0;
			$filter         = strtolower((string)$input->get('filter_search', ''));
			foreach ($events as $event) {
				$text = strtolower($event->title . ' ' . $event->description . ' ' . $event->url);
				if ($filter !== '' && $filter !== '0' && !str_contains($text, $filter)) {
					continue;
				}

				// Check if the event is within the date range
				if ($event->original_id > -1
					&& (($event->start_date < $startDateAsSQL && $event->end_date < $startDateAsSQL)
						|| ($event->start_date > $startDateAsSQL && $event->end_date > $endDateAsSQL))
				) {
					continue;
				}

				$eventData = (array)$event;

				if (!isset($event->locations)) {
					$event->locations = [];
				}

				$eventData['location_ids'] = array_map(static fn ($l) => $l->id, $event->locations);

				// Setting the reference to the old event
				$xreference              = $eventData['id'];
				$eventData['xreference'] = $xreference;

				unset($eventData['id']);
				unset($eventData['locations']);
				$eventData['alias'] = empty($event->alias) ? ApplicationHelper::stringURLSafe($event->title) : $event->alias;
				$eventData['catid'] = $category->id;

				// Find an existing event with the same xreference
				$table = $this->getTable('Event', 'Administrator');
				$table->load(['xreference' => $xreference]);
				if ($table->id) {
					$eventData['id']          = $table->id;
					$eventData['original_id'] = $table->original_id;
				}


				$model = $this->bootComponent('dpcalendar')->getMVCFactory()->createModel('Form', 'Site');
				$model->getState();

				if (!$model->save($eventData)) {
					$app->enqueueMessage($model->getError(), 'warning');
					continue;
				}

				empty($eventData['id']) ? $counter++ : $counterUpdated++;
			}
			$msgs[] = \sprintf(Text::_('COM_DPCALENDAR_N_ITEMS_CREATED'), $counter, $cal->title);
			$msgs[] = \sprintf(Text::_('COM_DPCALENDAR_N_ITEMS_UPDATED'), $counterUpdated, $cal->title);
		}
		$this->setState('messages', $msgs);
	}

	public function importGeoDB(): void
	{
		// The folder with the data
		$geoDBDirectory = JPATH_CACHE . '/com_dpcalendar-geodb';

		// Only update when we are not in free mode
		if (DPCalendarHelper::isFree()) {
			return;
		}

		// Fetch the content
		$content = (new CurlClient())->get('https://iptoasn.com/data/ip2country-v4-u32.tsv.gz')->dp->body;
		if (empty($content)) {
			throw new \Exception("Can't download the geolocation database from iptoasn.com. Is the site blocked through a firewall?");
		}

		if ($content instanceof \Exception) {
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
		while (!gzeof($zp)) {
			$uncompressed .= @gzread($zp, 102400);
		}
		// Close the zip reader
		@gzclose($zp);
		// Delete the zip file
		@unlink($geoDBDirectory . '/tmp.gz');

		$addresses = preg_split("/\r\n|\n|\r/", $uncompressed);
		if ($addresses === false) {
			return;
		}

		// Read the uncompressed content line by line
		$files = [];
		foreach ($addresses as $line) {
			if (str_starts_with($line, '#')) {
				continue;
			}

			// Parse the line
			$data = explode("\t", $line);
			if (\count($data) < 3 || $data[2] === 'None') {
				continue;
			}

			// Filename contains the first part of the IP
			$fileName = current(explode('.', long2ip((int)$data[0]) ?: '')) . '.php';

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
			$buffer .= "['" . $data[0] . "', '" . $data[1] . "', '" . $data[2] . "']," . PHP_EOL;

			// Write the buffer
			file_put_contents($geoDBDirectory . '/' . $fileName, $buffer, FILE_APPEND | LOCK_EX);
		}

		// Close the main array
		foreach ($files as $file) {
			file_put_contents($file, '];', FILE_APPEND | LOCK_EX);
		}
	}

	public function getTable($type = 'Location', $prefix = 'Administrator', $config = [])
	{
		return parent::getTable($type, $prefix, $config);
	}
}
