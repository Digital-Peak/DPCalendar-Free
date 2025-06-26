<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2014 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Component\DPCalendar\Site\View\Map;

\defined('_JEXEC') or die();

use DigitalPeak\Component\DPCalendar\Administrator\View\BaseView;
use DigitalPeak\Component\DPCalendar\Site\View\CalendarViewTrait;
use Joomla\CMS\Factory;

class HtmlView extends BaseView
{
	use CalendarViewTrait;

	protected array $calendars = [];

	public function display($tpl = null): void
	{
		$model = Factory::getApplication()->bootComponent('dpcalendar')->getMVCFactory()->createModel(
			'Events',
			'Site',
			['name' => $this->getName() . '.' . Factory::getApplication()->getInput()->getInt('Itemid', 0)]
		);
		$this->setModel($model, true);

		parent::display($tpl);
	}

	protected function init(): void
	{
		// Get the calendars and their childs
		$model = $this->getDPCalendar()->getMVCFactory()->createModel('Calendar', 'Site', ['ignore_request' => true]);
		$model->getState();
		$model->setState('filter.parentIds', $this->params->get('ids', '-1'));

		// The calendar ids
		$this->calendars = $model->getItems();
		foreach ($this->calendars as $calendar) {
			$this->fillCalendar($calendar);
		}

		$this->params->set('event_form_date_format', $this->params->get('map_date_format', 'd.m.Y'));

		$this->prepareForm($this->calendars);

		$this->filterForm->setFieldAttribute('start-date', 'formatted', true, 'list');
		$this->filterForm->setFieldAttribute('end-date', 'formatted', true, 'list');
	}
}
