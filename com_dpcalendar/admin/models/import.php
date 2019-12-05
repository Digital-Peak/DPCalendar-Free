<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2019 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */

defined('_JEXEC') or die();

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

	public function getTable($type = 'Location', $prefix = 'DPCalendarTable', $config = array())
	{
		return parent::getTable($type, $prefix, $config);
	}

	public function patch($fileName, $revert = false)
	{
		JLoader::import('joomla.filesystem.folder');
		JLoader::import('joomla.filesystem.file');

		if (!$this->canPatch()) {
			$this->setError("Patch executable not available or it is not possible to execute the binary");

			return false;
		}

		$content = file_get_contents($fileName);
		$tokens  = [
			'com_dpcalendar/admin/' => 'administrator/components/com_dpcalendar/',
			'com_dpcalendar/site/'  => 'components/com_dpcalendar/',
			'com_dpcalendar/media/' => 'media/com_dpcalendar/'
		];

		foreach (\Joomla\CMS\Filesystem\Folder::folders(JPATH_ROOT . '/modules', 'dpcalendar') as $folder) {
			$tokens[$folder . '/media/'] = 'media/' . $folder . '/';
			$tokens[$folder . '/']       = 'modules/' . $folder . '/';
		}
		foreach (\Joomla\CMS\Filesystem\Folder::folders(JPATH_PLUGINS) as $folder) {
			$tokens['plg_' . $folder . '_'] = 'plugins/' . $folder . '/';
		}

		foreach ($tokens as $search => $replace) {
			$content = str_replace('--- ' . $search, '--- ' . $replace, $content);
			$content = str_replace('+++ ' . $search, '+++ ' . $replace, $content);
		}

		file_put_contents($fileName . '.mod', $content);

		// Dry run
		$result = shell_exec('patch --dry-run -p0 ' . ($revert ? '-R' : '') . ' -d' . JPATH_ROOT . '/ -i ' . $fileName . '.mod');
		if (!$result || strpos($result, 'Skip this patch?') !== false) {
			$this->setError("Can't apply patch " . $fileName . " Output is:" . PHP_EOL . PHP_EOL . $result);
			JFile::delete($fileName . '.mod');

			return false;
		}

		$result = shell_exec('patch -b -p0 ' . ($revert ? '-R' : '') . ' -d' . JPATH_ROOT . '/ -i ' . $fileName . '.mod');
		JFile::delete($fileName . '.mod');
		$result = explode(PHP_EOL, $result);

		$files = [];
		foreach ($result as $line) {
			if (strpos($line, 'patching file ') === 0) {
				$files[] = str_replace('patching file ', '', $line);
			}
		}

		return $files;
	}

	public function canPatch()
	{
		if (!is_callable('shell_exec') || stripos(ini_get('disable_functions'), 'shell_exec') !== false) {
			return false;
		}

		return strpos(shell_exec('patch --version'), 'GNU patch') !== false;
	}
}
