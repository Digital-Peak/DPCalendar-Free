<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2014 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

defined('_JEXEC') or die();

use DPCalendar\Helper\Translation;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\LanguageHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;

JLoader::import('joomla.filesystem.folder');

class DPCalendarControllerTranslate extends BaseController
{
	public $input;
	public function fetch(): void
	{
		// The resource to fetch
		$resource = $this->input->getCmd('resource');
		if (!$resource) {
			echo json_encode([]);
			Factory::getApplication()->close();
		}

		// The stats data
		$data = ['resource' => $resource, 'languages' => []];

		// Loop over the local languages
		foreach (LanguageHelper::getKnownLanguages() as $language) {
			foreach (Translation::getResourceStats($resource) as $tr) {
				if ($tr->code != $language['tag']) {
					continue;
				}

				$data['languages'][] = [
					'tag'     => $tr->code,
					'percent' => $tr->translated_percent
				];
			}
		}

		// Send the data
		echo json_encode($data);
		Factory::getApplication()->close();
	}

	public function update()
	{
		// The resource to update
		$resource = $this->input->getCmd('resource');
		if (!$resource) {
			return false;
		}

		// Teh local languages
		$localLanguages = [];
		foreach (LanguageHelper::getKnownLanguages() as $language) {
			$localLanguages[$language['tag']] = $language;
		}

		// The stats
		foreach (Translation::getResourceStats($resource) as $tr) {
			// Ignore empty languages
			if ((int)$tr->translated === 0) {
				continue;
			}

			// Ignore en-GB
			if ($tr->code === 'en-GB') {
				continue;
			}

			// Check if the code is ok and if we have the language installed locally
			if (!array_key_exists($tr->code, $localLanguages)) {
				continue;
			}

			// Get the content of the language
			$content = Translation::getResourceStrings($resource, $tr->code);
			if (!$content) {
				continue;
			}

			// Compile the path of the file for the actual resource
			$path = '';
			if (strpos($resource, 'com_') !== false) {
				$path = strpos($resource, '-admin') !== false ? JPATH_ADMINISTRATOR : JPATH_ROOT;
				$path .= '/components/com_dpcalendar/language/' . $tr->code . '/' . $tr->code . '.'
					. str_replace(['-', '.admin', '.site'], ['.', '', ''], $resource);
			}

			if (strpos($resource, 'mod_') === 0) {
				$mod  = str_replace('-sys', '', $resource);
				$path = JPATH_ROOT;
				$path .= '/modules/' . $mod . '/language/' . $tr->code . '/' . $tr->code . '.' . $mod;
			}

			if (strpos($resource, 'plg_') === 0) {
				$db = Factory::getDbo();
				$db->setQuery("SELECT *  FROM `#__extensions` WHERE  `name` LIKE  '" . str_replace('-sys', '', $resource) . "'");
				$plugin = $db->loadObject();
				if (!empty($plugin)) {
					$path = JPATH_PLUGINS . '/';
					$path .= $plugin->folder . '/' . $plugin->element . '/language/' . $tr->code . '/' . $tr->code . '.' . $plugin->name;
				}
			}

			// Check if it is a sys path
			$path .= strpos($resource, '-sys') !== false && strpos($path, '.sys') === false ? '.sys' : '';
			$path .= '.ini';

			// When the file doesn't exist, ignore it
			if (!is_dir(dirname($path))) {
				mkdir(dirname($path), 0777, true);
			}

			// Write the content
			file_put_contents($path, $content);
		}

		// Sent the conformation
		DPCalendarHelper::sendMessage(
			Text::sprintf('COM_DPCALENDAR_VIEW_TOOLS_TRANSLATE_UPDATE_RESOURCE_SUCCESS', $resource),
			false,
			['resource' => $resource]
		);
	}
}
