<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2019 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Component\DPCalendar\Administrator\Field;

\defined('_JEXEC') or die();

use Joomla\CMS\Factory;
use Joomla\CMS\Form\Field\ListField;
use Joomla\CMS\HTML\HTMLHelper;

class DpcountriesField extends ListField
{
	protected $type = 'Dpcountries';

	protected function getOptions(): array
	{
		$lang = Factory::getApplication()->getLanguage();
		$lang->load('com_dpcalendar.countries', JPATH_ADMINISTRATOR . '/components/com_dpcalendar');


		$model = Factory::getApplication()->bootComponent('dpcalendar')->getMVCFactory()->createModel('Countries', 'Administrator', ['ignore_request' => true]);
		$model->setState('filter.state', 1);

		$options = [HTMLHelper::_('select.option', '', '')];
		foreach ($model->getItems() as $country) {
			$options[] = HTMLHelper::_('select.option', $country->id, $lang->_('COM_DPCALENDAR_COUNTRY_' . $country->short_code));
		}

		return $options;
	}
}
