<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2014 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Component\DPCalendar\Administrator\View\Tools;

\defined('_JEXEC') or die();

use DigitalPeak\Component\DPCalendar\Administrator\View\BaseView;
use Joomla\CMS\Helper\ContentHelper;
use Joomla\CMS\Language\LanguageHelper;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Toolbar\ToolbarHelper;

class HtmlView extends BaseView
{
	/** @var array */
	protected $resources;

	/** @var array */
	protected $languages;

	/** @var array */
	protected $calendars;

	/** @var array */
	protected $plugins;

	protected function init(): void
	{
		if (str_contains($this->getLayout(), 'translate')) {
			$this->resources = $this->get('ResourcesFromTranslation');

			$this->languages = LanguageHelper::getKnownLanguages();
			foreach ($this->languages as $language) {
				if ($language['tag'] == 'en-GB') {
					unset($this->languages[$language['tag']]);
				}
			}
		}

		if (str_contains($this->getLayout(), 'import')) {
			PluginHelper::importPlugin('dpcalendar');

			$tmp             = $this->app->triggerEvent('onCalendarsFetch');
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
				$this->app->getLanguage()->load('plg_dpcalendar_' . $plugin->name, JPATH_PLUGINS . '/dpcalendar/' . $plugin->name);
			}
		}
	}

	protected function addToolbar(): void
	{
		if (str_contains($this->getLayout(), 'import') && ContentHelper::getActions('com_dpcalendar')->get('core.create')) {
			ToolbarHelper::custom('import.add', 'new.png', 'new.png', 'COM_DPCALENDAR_VIEW_TOOLS_IMPORT', false);
			$this->title = 'COM_DPCALENDAR_MANAGER_TOOLS_IMPORT';
			$this->icon  = 'import';
		}

		if (str_contains($this->getLayout(), 'translate')) {
			ToolbarHelper::custom('translate.update', 'new.png', 'new.png', 'COM_DPCALENDAR_VIEW_TOOLS_TRANSLATE_UPDATE', false);
			$this->title = 'COM_DPCALENDAR_MANAGER_TOOLS_TRANSLATE';
			$this->icon  = 'translation';
		}

		parent::addToolbar();
	}
}
