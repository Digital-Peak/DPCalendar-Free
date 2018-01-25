<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2018 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */
defined('_JEXEC') or die();

use DPCalendar\Helper\Transifex;

JLoader::import('joomla.filesystem.folder');

class DPCalendarControllerTranslate extends JControllerLegacy
{

	public function fetch()
	{
		$resource = JFactory::getApplication()->input->getCmd('resource');

		if (!$resource) {
			return false;
		}

		$data = array(
			'resource'  => $resource,
			'languages' => array()
		);
		foreach (JFactory::getLanguage()->getKnownLanguages() as $language) {
			$resourceData       = Transifex::getData('resource/' . $resource . '/stats');
			$transifexLanguages = json_decode($resourceData['data']);
			foreach ($transifexLanguages as $langCode => $tr) {
				$code = Transifex::getLangCode($langCode);
				if ($code === false || $code != $language['tag']) {
					continue;
				}

				$data['languages'][] = array(
					'tag'     => $code,
					'percent' => (int)$tr->completed
				);
			}
		}

		echo json_encode($data);
		JFactory::getApplication()->close();
	}

	public function update()
	{
		$resource = JFactory::getApplication()->input->getCmd('resource');

		if (!$resource) {
			return false;
		}

		$resourceData = Transifex::getData('resource/' . $resource . '/stats');
		foreach ((array)json_decode($resourceData['data']) as $langCode => $lang) {
			if ((int)$lang->completed < 1) {
				continue;
			}
			$code = Transifex::getLangCode($langCode);
			if ($code === false) {
				continue;
			}

			$content = Transifex::getData('resource/' . $resource . '/translation/' . $code . '?file=1');

			if (empty($content['data'])) {
				continue;
			}

			$path = '';
			if (strpos($resource, 'com_') !== false) {
				$path = strpos($resource, '-admin') !== false ? JPATH_ADMINISTRATOR : JPATH_ROOT;
				$path .= '/components/com_dpcalendar/language/' . $code . '/' . $code . '.com_dpcalendar';
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
			$path .= strpos($resource, '-sys') !== false ? '.sys' : '';
			$path .= '.ini';

			if (empty($path) || !JFile::exists($path)) {
				continue;
			}

			JFile::write($path, $content['data']);
		}

		DPCalendarHelper::sendMessage(
			JText::sprintf('COM_DPCALENDAR_VIEW_TOOLS_TRANSLATE_UPDATE_RESOURCE_SUCCESS', $resource),
			false,
			array('resource' => $resource)
		);
	}
}
