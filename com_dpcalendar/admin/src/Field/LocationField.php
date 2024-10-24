<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2015 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Component\DPCalendar\Administrator\Field;

\defined('_JEXEC') or die();

use Joomla\CMS\Factory;
use Joomla\CMS\Form\Field\ListField;
use Joomla\CMS\HTML\HTMLHelper;

class LocationField extends ListField
{
	public $type = 'Location';

	protected function getOptions()
	{
		$options = parent::getOptions();

		$model = Factory::getApplication()->bootComponent('dpcalendar')->getMVCFactory()->createModel('Locations', 'Administrator', ['ignore_request' => true]);
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
