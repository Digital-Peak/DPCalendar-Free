<?php
/**
 * @package   DPCalendar
 * @author    Digital Peak http://www.digital-peak.com
 * @copyright Copyright (C) 2007 - 2020 Digital Peak. All rights reserved.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */
defined('_JEXEC') or die();

JLoader::import('components.com_dpcalendar.helpers.dpcalendar', JPATH_ADMINISTRATOR);
JFormHelper::loadFieldClass('groupedlist');

class JFormFieldDpcrooms extends JFormFieldGroupedList
{
	protected $type = 'Dpcrooms';

	public function getGroups()
	{
		if (!$this->form->getValue('location_ids')) {
			return parent::getGroups();
		}

		JModelLegacy::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_dpcalendar/models', 'DPCalendarModel');

		$groups = parent::getGroups();
		foreach ($this->form->getValue('location_ids') as $locationId) {
			$model    = JModelLegacy::getInstance('Location', 'DPCalendarModel', ['ignore_request' => true]);
			$location = $model->getItem($locationId);

			if (!$location->id || !$location->rooms) {
				continue;
			}

			$groups[$location->title] = [];

			foreach ($location->rooms as $room) {
				$groups[$location->title][] = JHtml::_('select.option', $location->id . '-' . $room->id, $room->title);
			}
		}

		return $groups;
	}
}
