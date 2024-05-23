<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2024 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Component\DPCalendar\Administrator\Model;

defined('_JEXEC') or die();

use Joomla\CMS\MVC\Model\BaseDatabaseModel;

class PluginModel extends BaseDatabaseModel
{
	public function getPluginData(string $pluginName): \stdClass
	{
		$db    = $this->getDatabase();
		$query = $db->getQuery(true)
			->select('folder AS type, element AS name, params')
			->from('#__extensions')
			->where('type = ' . $db->quote('plugin'))
			->where('folder = ' . $db->quote('dpcalendar'))
			->where('element = ' . $db->quote($pluginName));

		return $db->setQuery($query)->loadObject();
	}
}
