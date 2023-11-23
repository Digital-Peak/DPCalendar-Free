<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2018 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DPCalendar\Helper;

defined('_JEXEC') or die();

class LayoutHelper
{
	public function renderLayout($layout, $data = [])
	{
		if (is_array($data) && !array_key_exists('layoutHelper', $data) && strpos($layout, 'joomla.') !== 0) {
			$data['layoutHelper'] = $this;
		}

		return \Joomla\CMS\Layout\LayoutHelper::render($layout, $data, null, ['component' => 'com_dpcalendar', 'client' => 0]);
	}
}
