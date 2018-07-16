<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2018 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */
defined('_JEXEC') or die();

class DPCalendarViewTools extends \DPCalendar\View\BaseView
{
	protected function init()
	{
		if (strpos($this->getLayout(), 'translate') !== false) {
			$this->resources = $this->get('ResourcesFromTransifex');

			foreach ($this->resources as $resource) {
				$name           = str_replace(array('-', '_'), ' ', $resource->name);
				$name           = ucwords($name);
				$name           = str_replace('Plg', 'Plugin', $name);
				$name           = str_replace('Mod', 'Module', $name);
				$name           = str_replace('Com', 'Component', $name);
				$resource->name = str_replace('Dpc', 'DPC', $name);
			}

			$this->languages = JLanguageHelper::getKnownLanguages();
			foreach ($this->languages as $language) {
				if ($language['tag'] == 'en-GB') {
					unset($this->languages[$language['tag']]);
				}
			}
		}
	}

	protected function addToolbar()
	{
		if (strpos($this->getLayout(), 'import') !== false && DPCalendarHelper::getActions()->get('core.create')) {
			JToolbarHelper::custom('import.add', 'new.png', 'new.png', 'COM_DPCALENDAR_VIEW_TOOLS_IMPORT', false);
			$this->title = 'COM_DPCALENDAR_MANAGER_TOOLS_IMPORT';
			$this->icon  = 'import';
		}
		if (strpos($this->getLayout(), 'translate') !== false) {
			JToolbarHelper::custom('translate.update', 'new.png', 'new.png', 'COM_DPCALENDAR_VIEW_TOOLS_TRANSLATE_UPDATE', false);
			$this->title = 'COM_DPCALENDAR_MANAGER_TOOLS_TRANSLATE';
			$this->icon  = 'translation';
		}
		parent::addToolbar();
	}
}
