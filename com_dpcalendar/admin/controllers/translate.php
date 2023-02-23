<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2014 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */
defined('_JEXEC') or die();

use DPCalendar\Helper\Transifex;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\LanguageHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;

JLoader::import('joomla.filesystem.folder');

class DPCalendarControllerTranslate extends BaseController
{
	public function fetch()
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
			foreach (Transifex::getResourceStats($resource) as $tr) {
				$code = Transifex::getLangCode($tr->relationships->language->data->id);
				if ($code === false || $code != $language['tag']) {
					continue;
				}

				$data['languages'][] = [
					'tag'     => $code,
					'percent' => (int)((100 / $tr->attributes->total_strings) * $tr->attributes->translated_strings)
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
		foreach (Transifex::getResourceStats($resource) as $tr) {
			// Ignore empty languages
			if ((int)$tr->attributes->translated_strings === 0) {
				continue;
			}

			// Get the joomla language code
			$code = Transifex::getLangCode($tr->relationships->language->data->id);

			// Ignore en-GB
			if ($code === 'en-GB') {
				continue;
			}

			// Check if the code is ok and if we have the language installed locally
			if ($code === false || !array_key_exists($code, $localLanguages)) {
				continue;
			}

			// Get the content of the language
			$content = Transifex::getResourceStrings($resource, $code);
			if (!$content) {
				continue;
			}

			// Compile the path of the file for the actual resource
			$path = '';
			if (strpos($resource, 'com_') !== false) {
				$path = strpos($resource, '-admin') !== false ? JPATH_ADMINISTRATOR : JPATH_ROOT;
				$path .= '/components/com_dpcalendar/language/' . $code . '/' . $code . '.'
					. str_replace(['-', '.admin', '.site'], ['.', '', ''], $resource);
			}

			if (strpos($resource, 'mod_') === 0) {
				$mod  = str_replace('-sys', '', $resource);
				$path = JPATH_ROOT;
				$path .= '/modules/' . $mod . '/language/' . $code . '/' . $code . '.' . $mod;
			}

			if (strpos($resource, 'plg_') === 0) {
				$db = Factory::getDbo();
				$db->setQuery("SELECT *  FROM `#__extensions` WHERE  `name` LIKE  '" . str_replace('-sys', '', $resource) . "'");
				$plugin = $db->loadObject();
				if (!empty($plugin)) {
					$path = JPATH_PLUGINS . '/';
					$path .= $plugin->folder . '/' . $plugin->element . '/language/' . $code . '/' . $code . '.' . $plugin->name;
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
