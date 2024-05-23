<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2014 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Component\DPCalendar\Administrator\Model;

defined('_JEXEC') or die();

use DigitalPeak\ThinHTTP\CurlClient;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

class ToolsModel extends BaseDatabaseModel
{
	private static string $key = 'wlu_3AcTx1VREAN4M5if9FXhhV7z9cDqeo9BnrFg';

	public function getResourcesFromTranslation(): array
	{
		$resources = (new CurlClient())->get(
			'https://translate.digital-peak.com/api/projects/dpcalendar/components/',
			self::$key
		)->results;
		if ($resources === []) {
			return [];
		}

		foreach ($resources as $key => $component) {
			if ($component->slug === 'glossary') {
				unset($resources[$key]);
				continue;
			}
		}

		return $resources;
	}

	public function getResourceStats(string $resourceId): array
	{
		return (new CurlClient())->get(
			'https://translate.digital-peak.com/api/components/dpcalendar/' . $resourceId . '/statistics/',
			self::$key
		)->results;
	}

	public function getResourceStrings(string $resourceId, string $language): string
	{
		$file = (new CurlClient())->get(
			'https://translate.digital-peak.com/api/translations/dpcalendar/' . $resourceId . '/' . $language . '/file/',
			self::$key
		);

		return $file->dp->body;
	}

	public function getPluginForName(string $name): ?\stdClass
	{
		$db = $this->getDatabase();
		$db->setQuery("SELECT *  FROM `#__extensions` WHERE  `name` LIKE  '" . str_replace('-sys', '', $name) . "'");

		return $db->loadObject();
	}
}
