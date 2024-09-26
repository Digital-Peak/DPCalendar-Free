<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2017 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Component\DPCalendar\Administrator\Field;

defined('_JEXEC') or die();

use Joomla\CMS\Factory;
use Joomla\CMS\Form\Field\GroupedlistField;
use Joomla\CMS\HTML\HTMLHelper;

class DpcroomsField extends GroupedlistField
{
	protected $type = 'Dpcrooms';

	protected function getGroups()
	{
		if (!$this->form->getValue('location_ids')) {
			return parent::getGroups();
		}

		$groups = parent::getGroups();
		foreach ($this->form->getValue('location_ids') as $locationId) {
			$model    = Factory::getApplication()->bootComponent('dpcalendar')->getMVCFactory()->createModel('Location', 'Administrator', ['ignore_request' => true]);
			$location = $model->getItem($locationId);

			if (!$location || !$location->id || !$location->rooms) {
				continue;
			}

			$groups[$location->title] = [];
			foreach ($location->rooms as $room) {
				$groups[$location->title][] = HTMLHelper::_('select.option', $location->id . '-' . $room->id, $room->title);
			}
		}

		return $groups;
	}
}
