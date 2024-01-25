<?php

use DPCalendar\View\BaseView;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\LanguageHelper;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Toolbar\ToolbarHelper;

/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2014 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */
defined('_JEXEC') or die();

class DPCalendarViewTools extends BaseView
{
	public $resources;
	public $languages;
	/**
	 * @var never[]|mixed[]
	 */
	public $calendars;
	public $plugins;
	protected function init()
	{
		if (strpos($this->getLayout(), 'translate') !== false) {
			$this->resources = $this->get('ResourcesFromTranslation');

			$this->languages = LanguageHelper::getKnownLanguages();
			foreach ($this->languages as $language) {
				if ($language['tag'] == 'en-GB') {
					unset($this->languages[$language['tag']]);
				}
			}
		}

		if (strpos($this->getLayout(), 'import') !== false) {
			PluginHelper::importPlugin('dpcalendar');

			$tmp             = Factory::getApplication()->triggerEvent('onCalendarsFetch');
			$this->calendars = [];
			if (!empty($tmp)) {
				foreach ($tmp as $tmpCalendars) {
					foreach ($tmpCalendars as $calendar) {
						$this->calendars[] = $calendar;
					}
				}
			}

			$this->plugins = PluginHelper::getPlugin('dpcalendar');
			foreach ($this->plugins as $plugin) {
				Factory::getLanguage()->load('plg_dpcalendar_' . $plugin->name, JPATH_PLUGINS . '/dpcalendar/' . $plugin->name);
			}
		}
	}

	protected function addToolbar()
	{
		if (strpos($this->getLayout(), 'import') !== false && DPCalendarHelper::getActions()->get('core.create')) {
			ToolbarHelper::custom('import.add', 'new.png', 'new.png', 'COM_DPCALENDAR_VIEW_TOOLS_IMPORT', false);
			$this->title = 'COM_DPCALENDAR_MANAGER_TOOLS_IMPORT';
			$this->icon  = 'import';
		}
		if (strpos($this->getLayout(), 'translate') !== false) {
			ToolbarHelper::custom('translate.update', 'new.png', 'new.png', 'COM_DPCALENDAR_VIEW_TOOLS_TRANSLATE_UPDATE', false);
			$this->title = 'COM_DPCALENDAR_MANAGER_TOOLS_TRANSLATE';
			$this->icon  = 'translation';
		}
		parent::addToolbar();
	}
}
