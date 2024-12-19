<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2018 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Component\DPCalendar\Administrator\Model;

\defined('_JEXEC') or die();

use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\MVC\Model\BaseModel;

class LayoutModel extends BaseModel
{
	public function renderLayout(string $layout, array $data = []): string
	{
		if (!\array_key_exists('layoutHelper', $data) && !str_starts_with($layout, 'joomla.')) {
			$data['layoutHelper'] = $this;
		}

		return LayoutHelper::render($layout, $data, '', ['component' => 'com_dpcalendar', 'client' => 0]);
	}
}
