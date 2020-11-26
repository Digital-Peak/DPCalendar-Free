<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2014 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */
defined('_JEXEC') or die();

use DPCalendar\Helper\Transifex;

JLoader::import('joomla.filesystem.folder');

class DPCalendarControllerTranslate extends JControllerLegacy
{
	public function fetch()
	{
		// The resource to fetch
		$resource = $this->input->getCmd('resource');
		if (!$resource) {
			echo json_encode([]);
			JFactory::getApplication()->close();
		}

		// The stats data
		$data = [
			'resource'  => $resource,
			'languages' => []
		];

		// Loop over the local languages
		foreach (JLanguageHelper::getKnownLanguages() as $language) {
			$resourceData       = Transifex::getData('resource/' . $resource . '/stats');
			$transifexLanguages = json_decode($resourceData['data']);
			foreach ($transifexLanguages as $langCode => $tr) {
				$code = Transifex::getLangCode($langCode);
				if ($code === false || $code != $language['tag']) {
					continue;
				}

				$data['languages'][] = ['tag' => $code, 'percent' => (int)$tr->completed];
			}
		}

		// Send the data
		echo json_encode($data);
		JFactory::getApplication()->close();
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
		foreach (JLanguageHelper::getKnownLanguages() as $language) {
			$localLanguages[$language['tag']] = $language;
		}

		// The stats
		$resourceData = Transifex::getData('resource/' . $resource . '/stats');
		foreach ((array)json_decode($resourceData['data']) as $langCode => $lang) {
			// Ignore empty languages
			if ((int)$lang->completed < 1) {
				continue;
			}

			// Get the joomla language code
			$code = Transifex::getLangCode($langCode);

			// Check if the code is ok and if we have the language installed locally
			if ($code === false || !array_key_exists($code, $localLanguages)) {
				continue;
			}

			// Get the content of the language
			$content = Transifex::getData('resource/' . $resource . '/translation/' . $code . '?file=1');
			if (empty($content['data']) || $content['info']['http_code'] > 200) {
				continue;
			}

			// Compile the path of the file for the actual resource
			$path = '';
			if (strpos($resource, 'com_') !== false) {
				$path = strpos($resource, '-admin') !== false ? JPATH_ADMINISTRATOR : JPATH_ROOT;
				$path .= '/components/com_dpcalendar/language/' . $code . '/' . $code . '.' . str_replace(['-', '.admin'], ['.', ''], $resource);
			}

			if (strpos($resource, 'mod_') === 0) {
				$mod  = str_replace('-sys', '', $resource);
				$path = JPATH_ROOT;
				$path .= '/modules/' . $mod . '/language/' . $code . '/' . $code . '.' . $mod;
			}

			if (strpos($resource, 'plg_') === 0) {
				$db = JFactory::getDbo();
				$db->setQuery("SELECT *  FROM `#__extensions` WHERE  `name` LIKE  '" . str_replace('-sys', '', $resource) . "'");
				$plugin = $db->loadObject();
				if (!empty($plugin)) {
					$path = JPATH_PLUGINS . '/';
					$path .= $plugin->folder . '/' . $plugin->element . '/language/' . $code . '/' . $code . '.' . $plugin->name;
				}
			}

			// Check if it is a sys path
			$path .= strpos($resource, '-sys') !== false ? '.sys' : '';
			$path .= '.ini';

			// When the file doesn't exist, ignore it
			if (empty($path) || !JFile::exists($path)) {
				continue;
			}

			// Write the content
			JFile::write($path, $content['data']);
		}

		// Sent the conformation
		DPCalendarHelper::sendMessage(
			JText::sprintf('COM_DPCALENDAR_VIEW_TOOLS_TRANSLATE_UPDATE_RESOURCE_SUCCESS', $resource),
			false,
			['resource' => $resource]
		);
	}
}
