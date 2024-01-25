<?php

use DPCalendar\View\BaseView;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2014 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */
defined('_JEXEC') or die();

class DPCalendarViewMap extends BaseView
{
	/**
	 * @var array<string, mixed>
	 */
	public $displayData;
	public $startDate;
	public $endDate;
	public function display($tpl = null)
	{
		BaseDatabaseModel::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_dpcalendar/models');
		$this->setModel(BaseDatabaseModel::getInstance('Events', 'DPCalendarModel'), true);

		return parent::display($tpl);
	}

	protected function init()
	{
		$this->displayData['format'] = $this->params->get('map_date_format', 'd.m.Y');

		$context = 'com_dpcalendar.map.';

		$this->state->set('filter.search', $this->app->getUserStateFromRequest($context . 'search', 'search'));
		$this->state->set('filter.location', $this->app->getUserStateFromRequest($context . 'location', 'location'));
		$this->state->set(
			'filter.radius',
			$this->app->getUserStateFromRequest($context . 'radius', 'radius', $this->params->get('map_view_radius', 20))
		);
		$this->state->set(
			'filter.length-type',
			$this->app->getUserStateFromRequest($context . 'length-type', 'length-type', $this->params->get('map_view_length_type', 'm'))
		);

		$this->state->set('list.start-date', $this->app->getUserStateFromRequest($context . 'start-date', 'start-date'));
		$this->state->set('list.end-date', $this->app->getUserStateFromRequest($context . 'end-date', 'end-date'));


		$this->startDate = $this->app->getUserStateFromRequest($context . 'start-date', 'start-date');
		if ($this->startDate) {
			$this->startDate = $this->dateHelper->getDate($this->startDate, true);
		}

		$this->endDate = $this->app->getUserStateFromRequest($context . 'end-date', 'end-date');
		if ($this->endDate) {
			$this->endDate = $this->dateHelper->getDate($this->endDate, true);
		}
	}
}
