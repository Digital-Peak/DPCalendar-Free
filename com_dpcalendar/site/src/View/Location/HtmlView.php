<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2016 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Component\DPCalendar\Site\View\Location;

defined('_JEXEC') or die();

use DigitalPeak\Component\DPCalendar\Administrator\Helper\DPCalendarHelper;
use DigitalPeak\Component\DPCalendar\Administrator\View\BaseView;
use Joomla\CMS\Factory;
use Joomla\CMS\Helper\TagsHelper;
use Joomla\CMS\HTML\Helpers\StringHelper;

class HtmlView extends BaseView
{
	/** @var \stdClass */
	protected $location;

	/** @var array */
	protected $ids;

	/** @var array */
	protected $events;

	/** @var array */
	protected $resources;

	/** @var string */
	protected $returnPage;

	/** @var int */
	protected $heading;

	public function display($tpl = null): void
	{
		$this->setModel(Factory::getApplication()->bootComponent('dpcalendar')->getMVCFactory()->createModel('Location', 'Administrator'), true);

		parent::display($tpl);
	}

	protected function init(): void
	{
		// @phpstan-ignore-next-line
		$this->location = $this->getModel()->getItem($this->input->getInt('id', 0));

		if (!$this->location || $this->location->id == null) {
			throw new \Exception($this->translate('COM_DPCALENDAR_ALERT_NO_AUTH'), 404);
		}

		$this->location->tags = new TagsHelper();
		$this->location->tags->getItemTags('com_dpcalendar.location', $this->location->id);

		$model = $this->app->bootComponent('dpcalendar')->getMVCFactory()->createModel('Calendar', 'Site');
		$model->getState();
		$model->setState('filter.parentIds', ['root']);

		$this->ids = [];
		foreach ($model->getItems() as $calendar) {
			$this->ids[] = $calendar->getId();
		}

		$model = $this->app->bootComponent('dpcalendar')->getMVCFactory()->createModel('Events', 'Site', ['ignore_request' => true]);
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

		$this->returnPage = $this->input->getInt('Itemid', 0) !== 0 ? 'index.php?Itemid=' . $this->input->getInt('Itemid', 0) : '';
	}

	protected function prepareDocument(): void
	{
		parent::prepareDocument();

		$menu = $this->app->getMenu()->getActive();

		$id = $menu && array_key_exists('id', $menu->query) ? (int)$menu->query['id'] : 0;
		if ($menu && ($menu->query['option'] != 'com_dpcalendar' || $menu->query['view'] != 'location' || $id != $this->location->id)) {
			$this->app->getPathway()->addItem($this->location->title, '');
		}

		$title = $this->location->title;
		if (!$title) {
			$title = $this->params->get('page_title', '');
		}
		$document = $this->getDocument();
		$document->setTitle($title);

		$metadesc = trim((string)$this->location->metadata->get('metadesc', ''));
		if ($metadesc === '' || $metadesc === '0') {
			$metadesc = StringHelper::truncate($this->location->description ?: '', 100, true, false);
		}
		if ($metadesc !== '' && $metadesc !== '0') {
			$document->setDescription($metadesc);
		}

		$mdata = $this->location->metadata->toArray();
		foreach ($mdata as $k => $v) {
			if ($v) {
				$document->setMetadata($k, $v);
			}
		}

		if ($this->params->get('location_show_page_heading', 0) != 2) {
			$this->params->set('show_page_heading', $this->params->get('location_show_page_heading', 0));
		}

		$this->heading = $this->params->get('show_page_heading') ? 1 : 0;
	}
}
