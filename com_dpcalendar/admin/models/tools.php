<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2014 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

defined('_JEXEC') or die();

use DPCalendar\Helper\Translation;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

class DPCalendarModelTools extends BaseDatabaseModel
{
	public function getResourcesFromTranslation()
	{
		$resources = Translation::getResources();
		if ($resources === []) {
			return;
		}

		foreach ($resources as $key => $component) {
			if ($component->slug === 'glossary') {
				unset($resources[$key]);
				continue;
			}
		}

		return $resources;
	}
}
