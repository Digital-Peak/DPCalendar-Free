<?php
/**
 * @package   DPCalendar
 * @author    Digital Peak http://www.digital-peak.com
 * @copyright Copyright (C) 2007 - 2019 Digital Peak. All rights reserved.
 * @license   http://www.gnu.org/licenses/gpl.html GNU/GPL
 */
defined('_JEXEC') or die();

JLoader::import('components.com_dpcalendar.helpers.schema', JPATH_ADMINISTRATOR);

class DPCalendarViewLocation extends \DPCalendar\View\BaseView
{

	public function display($tpl = null)
	{
		JModelLegacy::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_dpcalendar/models');
		$this->setModel(JModelLegacy::getInstance('Location', 'DPCalendarModel'), true);

		return parent::display($tpl);
	}

	public function init()
	{
		$this->location = $this->getModel()->getItem($this->input->getInt('id'));

		if ($this->location->id == null) {
			throw new Exception(JText::_('JERROR_ALERTNOAUTHOR'), 404);
		}

		JLoader::import('joomla.application.component.model');
		JModelLegacy::addIncludePath(JPATH_SITE . '/components/com_dpcalendar/models', 'DPCalendarModel');

		$model = JModelLegacy::getInstance('Calendar', 'DPCalendarModel');
		$model->getState();
		$model->setState('filter.parentIds', ['root']);

		$this->ids = [];
		foreach ($model->getItems() as $calendar) {
			$this->ids[] = $calendar->id;
		}

		$model = JModelLegacy::getInstance('Events', 'DPCalendarModel', ['ignore_request' => true]);
		$model->setState('list.limit', 25);
		$model->setState('list.start-date', DPCalendarHelper::getDate());
		$model->setState('list.ordering', 'start_date');
		$model->setState('filter.expand', $this->params->get('location_expand_events', 1));
		$model->setState('filter.ongoing', true);
		$model->setState('filter.state', 1);
		$model->setState('filter.language', JFactory::getLanguage());
		$model->setState('filter.locations', [$this->location->id]);
		$this->events = $model->getItems();

		$rooms = [];
		if ($this->location->rooms) {
			foreach ($this->location->rooms as $room) {
				$rooms[] = (object)['id' => $this->location->id . '-' . $room->id, 'title' => $room->title];
			}
		}

		$this->resources[] = (object)['id' => $this->location->id, 'title' => $this->location->title, 'children' => $rooms];

		$this->return = $this->input->getInt('Itemid', null) ? 'index.php?Itemid=' . $this->input->getInt('Itemid', null) : null;
	}

	protected function prepareDocument()
	{
		parent::prepareDocument();

		$title = $this->location->title;
		if (!$title) {
			$title = $this->params->get('page_title', '');
		}

		$this->document->setTitle($title);

		$metadesc = trim($this->location->metadata->get('metadesc'));
		if (!$metadesc) {
			$metadesc = JHtmlString::truncate($this->location->description, 100, true, false);
		}
		if ($metadesc) {
			$this->document->setDescription($metadesc);
		}

		$mdata = $this->location->metadata->toArray();
		foreach ($mdata as $k => $v) {
			if ($v) {
				$this->document->setMetadata($k, $v);
			}
		}
	}
}
