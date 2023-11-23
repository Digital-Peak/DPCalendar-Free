<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2015 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

defined('_JEXEC') or die();

use Joomla\CMS\Form\Field\ListField;
use Joomla\CMS\Form\FormHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Table\Table;

JLoader::import('components.com_dpcalendar.helpers.dpcalendar', JPATH_ADMINISTRATOR);
if (version_compare(JVERSION, 4, '<') && !class_exists('\\Joomla\\CMS\\Form\\Field\\ListField', false)) {
	FormHelper::loadFieldClass('list');
	class_alias('JFormFieldList', '\\Joomla\\CMS\\Form\\Field\\ListField');
}

class JFormFieldLocation extends ListField
{
	public $type = 'Location';

	protected function getOptions()
	{
		$options = parent::getOptions();

		Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_dpcalendar/tables');
		BaseDatabaseModel::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_dpcalendar/models', 'DPCalendarModel');
		$model = BaseDatabaseModel::getInstance('Locations', 'DPCalendarModel', ['ignore_request' => true]);
		$model->getState();
		$model->setState('list.limit', 0);
		$model->setState('filter.state', 1);
		foreach ($model->getItems() as $location) {
			$options[] = HTMLHelper::_(
				'select.option',
				$location->id,
				$location->title . ' [' . $location->latitude . ',' . $location->longitude . ']'
			);
		}

		return $options;
	}
}
