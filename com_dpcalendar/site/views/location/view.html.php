<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2016 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

defined('_JEXEC') or die();

use Joomla\CMS\Factory;
use Joomla\CMS\Helper\TagsHelper;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

class DPCalendarViewLocation extends \DPCalendar\View\BaseView
{
	public function display($tpl = null)
	{
		BaseDatabaseModel::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_dpcalendar/models');
		$this->setModel(BaseDatabaseModel::getInstance('Location', 'DPCalendarModel'), true);

		return parent::display($tpl);
	}

	public function init()
	{
		$this->location = $this->getModel()->getItem($this->input->getInt('id'));

		if ($this->location->id == null) {
			throw new Exception($this->translate('COM_DPCALENDAR_ALERT_NO_AUTH'), 404);
		}

		$this->location->tags = new TagsHelper();
		$this->location->tags->getItemTags('com_dpcalendar.location', $this->location->id);

		JLoader::import('joomla.application.component.model');
		BaseDatabaseModel::addIncludePath(JPATH_SITE . '/components/com_dpcalendar/models', 'DPCalendarModel');

		$model = BaseDatabaseModel::getInstance('Calendar', 'DPCalendarModel');
		$model->getState();
		$model->setState('filter.parentIds', ['root']);

		$this->ids = [];
		foreach ($model->getItems() as $calendar) {
			$this->ids[] = $calendar->id;
		}

		$model = BaseDatabaseModel::getInstance('Events', 'DPCalendarModel', ['ignore_request' => true]);
		$model->setState('list.limit', 25);
		$model->setState('list.start-date', DPCalendarHelper::getDate());
		$model->setState('list.ordering', 'start_date');
		$model->setState('filter.expand', $this->params->get('location_expand_events', 1));
		$model->setState('filter.ongoing', true);
		$model->setState('filter.state', [1, 3]);
		$model->setState('filter.language', $this->app->getLanguage()->getTag());
		$model->setState('filter.locations', [$this->location->id]);
		$this->events = $model->getItems();

		$rooms = [];
		if ($this->location->rooms) {
			foreach ($this->location->rooms as $room) {
				$rooms[] = (object)['id' => $this->location->id . '-' . $room->id, 'title' => $room->title];
			}
		}

		$this->resources[] = (object)['id' => $this->location->id, 'title' => $this->location->title, 'children' => $rooms];

		$this->returnPage = $this->input->getInt('Itemid', null) ? 'index.php?Itemid=' . $this->input->getInt('Itemid', null) : null;
	}

	protected function prepareDocument()
	{
		parent::prepareDocument();

		$title = $this->location->title;
		if (!$title) {
			$title = $this->params->get('page_title', '');
		}

		$this->document->setTitle($title);

		$metadesc = trim($this->location->metadata->get('metadesc', ''));
		if (!$metadesc) {
			$metadesc = JHtmlString::truncate($this->location->description ?: '', 100, true, false);
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

		if ($this->params->get('location_show_page_heading', 0) != 2) {
			$this->params->set('show_page_heading', $this->params->get('location_show_page_heading', 0));
		}

		$this->heading = $this->params->get('show_page_heading') ? 1 : 0;
	}
}
