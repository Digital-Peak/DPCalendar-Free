<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2017 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

defined('_JEXEC') or die();

use Joomla\CMS\Form\Field\GroupedlistField;
use Joomla\CMS\Form\FormHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

if (version_compare(JVERSION, 4, '<')) {
	FormHelper::loadFieldClass('groupedlist');
	class_alias('JFormFieldGroupedList', '\\Joomla\\CMS\\Form\\Field\\GroupedlistField');
}

class JFormFieldDpcrooms extends GroupedlistField
{
	public $form;
	protected $type = 'Dpcrooms';

	public function getGroups()
	{
		if (!$this->form->getValue('location_ids')) {
			return parent::getGroups();
		}

		JLoader::import('components.com_dpcalendar.helpers.dpcalendar', JPATH_ADMINISTRATOR);

		BaseDatabaseModel::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_dpcalendar/models', 'DPCalendarModel');

		$groups = parent::getGroups();
		foreach ($this->form->getValue('location_ids') as $locationId) {
			$model    = BaseDatabaseModel::getInstance('Location', 'DPCalendarModel', ['ignore_request' => true]);
			$location = $model->getItem($locationId);

			if (!$location->id || !$location->rooms) {
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
