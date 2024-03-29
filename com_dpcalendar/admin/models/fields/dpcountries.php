<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2019 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

defined('_JEXEC') or die();

use Joomla\CMS\Factory;
use Joomla\CMS\Form\Field\ListField;
use Joomla\CMS\Form\FormHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

if (version_compare(JVERSION, 4, '<') && !class_exists('\\Joomla\\CMS\\Form\\Field\\ListField', false)) {
	FormHelper::loadFieldClass('list');
	class_alias('JFormFieldList', '\\Joomla\\CMS\\Form\\Field\\ListField');
}

class JFormFieldDpcountries extends ListField
{
	protected $type = 'Dpcountries';

	public function getOptions()
	{
		JLoader::import('components.com_dpcalendar.helpers.dpcalendar', JPATH_ADMINISTRATOR);

		$lang = Factory::getApplication()->getLanguage();
		$lang->load('com_dpcalendar.countries', JPATH_ADMINISTRATOR . '/components/com_dpcalendar');

		BaseDatabaseModel::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_dpcalendar/models', 'DPCalendarModel');
		$model = BaseDatabaseModel::getInstance('Countries', 'DPCalendarModel', ['ignore_request' => true]);
		$model->setState('filter.state', 1);

		$options = [HTMLHelper::_('select.option', '', '')];
		foreach ($model->getItems() as $country) {
			$options[] = HTMLHelper::_('select.option', $country->id, $lang->_('COM_DPCALENDAR_COUNTRY_' . $country->short_code));
		}

		return $options;
	}
}
