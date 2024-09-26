<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2014 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Component\DPCalendar\Administrator\Controller;

\defined('_JEXEC') or die();

use DigitalPeak\Component\DPCalendar\Administrator\Helper\DPCalendarHelper;
use DigitalPeak\Component\DPCalendar\Administrator\Model\ToolsModel;
use Joomla\CMS\Language\LanguageHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;

class TranslateController extends BaseController
{
	public function fetch(): void
	{
		// The resource to fetch
		$resource = $this->input->getCmd('resource', '');
		if ($resource === '' || $resource === '0') {
			echo json_encode([]);
			$this->app->close();
		}

		// The stats data
		$data = ['resource' => $resource, 'languages' => []];

		// Loop over the local languages
		foreach (LanguageHelper::getKnownLanguages() as $language) {
			foreach ($this->getModel('Tools', 'Administrator')->getResourceStats($resource) as $tr) {
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
		$this->app->close();
	}

	public function update(): void
	{
		// The resource to update
		$resource = $this->input->getCmd('resource');
		if ($resource === '' || $resource === '0') {
			return;
		}

		// Teh local languages
		$localLanguages = [];
		foreach (LanguageHelper::getKnownLanguages() as $language) {
			$localLanguages[$language['tag']] = $language;
		}

		/** @var ToolsModel $model */
		$model = $this->getModel('Tools', 'Administrator');

		// The stats
		foreach ($model->getResourceStats($resource) as $tr) {
			// Ignore empty languages
			if ((int)$tr->translated === 0) {
				continue;
			}

			// Ignore en-GB
			if ($tr->code === 'en-GB') {
				continue;
			}

			// Check if the code is ok and if we have the language installed locally
			if (!\array_key_exists($tr->code, $localLanguages)) {
				continue;
			}

			// Get the content of the language
			$content = $model->getResourceStrings($resource, (string)$tr->code);
			if (!$content) {
				continue;
			}

			// Compile the path of the file for the actual resource
			$path = '';
			if (str_contains($resource, 'com_')) {
				$path = str_contains($resource, '-admin') ? JPATH_ADMINISTRATOR : JPATH_ROOT;
				$path .= '/components/com_dpcalendar/language/' . $tr->code . '/' . $tr->code . '.'
					. str_replace(['-', '.admin', '.site'], ['.', '', ''], $resource);
			}

			if (str_starts_with($resource, 'mod_')) {
				$mod  = str_replace('-sys', '', $resource);
				$path = JPATH_ROOT;
				$path .= '/modules/' . $mod . '/language/' . $tr->code . '/' . $tr->code . '.' . $mod;
			}

			if (str_starts_with($resource, 'plg_')) {
				$plugin = $model->getPluginForName(str_replace('-sys', '', $resource));
				if ($plugin !== null) {
					$path = JPATH_PLUGINS . '/';
					$path .= $plugin->folder . '/' . $plugin->element . '/language/' . $tr->code . '/' . $tr->code . '.' . $plugin->name;
				}
			}

			// Check if it is a sys path
			$path .= str_contains($resource, '-sys') ? '' : '.sys';
			$path .= '.ini';

			// When the file doesn't exist, ignore it
			if (!is_dir(\dirname($path))) {
				mkdir(\dirname($path), 0777, true);
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
