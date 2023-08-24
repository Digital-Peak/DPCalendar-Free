<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2018 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DPCalendar\Helper;

use Joomla\CMS\Factory;

defined('_JEXEC') or die();

class UserHelper
{
	public function getUser($id = null)
	{
		return Factory::getUser($id);
	}
}
