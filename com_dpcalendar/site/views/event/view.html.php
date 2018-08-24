<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2018 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */
defined('_JEXEC') or die();

JLoader::import('components.com_dpcalendar.helpers.schema', JPATH_ADMINISTRATOR);

class DPCalendarViewEvent extends \DPCalendar\View\BaseView
{

	protected $event;

	public function init()
	{
		if ($this->getLayout() == 'empty') {
			return;
		}

		$event = $this->get('Item');

		if ($event == null || !$event->id) {
			throw new Exception(JText::_('COM_DPCALENDAR_ERROR_EVENT_NOT_FOUND'), 404);
		}

		if ($this->params->get('event_redirect_to_url', 0) && $event->url) {
			$this->app->redirect($event->url);
		}

		// Add router helpers.
		$event->slug = $event->alias ? ($event->id . ':' . $event->alias) : $event->id;

		// Check the access to the event
		$levels = JFactory::getUser()->getAuthorisedViewLevels();

		if (!in_array($event->access, $levels) ||
			((in_array($event->access, $levels) && (isset($event->category_access) && !in_array($event->category_access, $levels))))
		) {
			$this->setError(JText::_('JERROR_ALERTNOAUTHOR'));

			return false;
		}

		$event->tags = new JHelperTags();
		$event->tags->getItemTags('com_dpcalendar.event', $event->id);

		JPluginHelper::importPlugin('content');

		$event->text = $event->description;
		JFactory::getApplication()->triggerEvent(
			'onContentPrepare',
			array(
				'com_dpcalendar.event',
				&$event,
				&$event->params,
				0
			)
		);
		$event->description = $event->text;

		$event->displayEvent                    = new stdClass();
		$results                                = JFactory::getApplication()->triggerEvent(
			'onContentAfterTitle',
			array(
				'com_dpcalendar.event',
				&$event,
				&$event->params,
				0
			)
		);
		$event->displayEvent->afterDisplayTitle = trim(implode("\n", $results));

		$results                                   = JFactory::getApplication()->triggerEvent(
			'onContentBeforeDisplay',
			array(
				'com_dpcalendar.event',
				&$event,
				&$event->params,
				0
			)
		);
		$event->displayEvent->beforeDisplayContent = trim(implode("\n", $results));

		$results                                  = JFactory::getApplication()->triggerEvent(
			'onContentAfterDisplay',
			array(
				'com_dpcalendar.event',
				&$event,
				&$event->params,
				0
			)
		);
		$event->displayEvent->afterDisplayContent = trim(implode("\n", $results));

		$this->event = $event;

		$model = $this->getModel();
		$model->hit();

		$this->roomTitles = [];
		if ($event->locations && !empty($this->event->rooms)) {
			foreach ($event->locations as $location) {
				if (empty($location->rooms)) {
					continue;
				}

				foreach ($this->event->rooms as $room) {
					list($locationId, $roomId) = explode('-', $room, 2);

					foreach ($location->rooms as $lroom) {
						if ($locationId != $location->id || $roomId != $lroom->id) {
							continue;
						}

						$this->roomTitles[$room] = $lroom->title;
					}
				}
			}
		}

		$this->avatar     = '';
		$this->authorName = '';
		$author           = JFactory::getUser($event->created_by);
		if ($author) {
			$this->authorName = $event->created_by_alias ? $event->created_by_alias : $author->name;

			if (JFile::exists(JPATH_ADMINISTRATOR . '/components/com_comprofiler/plugin.foundation.php')) {
				// Set the community builder username as content
				include_once(JPATH_ADMINISTRATOR . '/components/com_comprofiler/plugin.foundation.php');
				$cbUser = CBuser::getInstance($event->created_by);
				if ($cbUser) {
					$this->authorName = $cbUser->getField('formatname', null, 'html', 'none', 'list', 0, true);
				}
			}

			$this->avatar = DPCalendarHelper::getAvatar($author->id, $author->email, $this->params);
		}

		$this->event->contact_link = '';
		if (!empty($event->contactid)) {
			JLoader::register('ContactHelperRoute', JPATH_SITE . '/components/com_contact/helpers/route.php');
			$this->event->contact_link = JRoute::_(
				ContactHelperRoute::getContactRoute($event->contactid . ':' . $event->contactalias, $event->contactcatid)
			);
		}
		$this->displayData['event'] = $this->event;

		return parent::init();
	}

	protected function prepareDocument()
	{
		if ($this->getLayout() == 'empty') {
			return;
		}

		parent::prepareDocument();

		$app     = JFactory::getApplication();
		$menus   = $app->getMenu();
		$pathway = $app->getPathway();
		$title   = null;

		// Because the application sets a default page title,
		// we need to get it from the menu item itself
		$menu = $menus->getActive();

		$title = $this->params->get('page_title', '');

		$id = (int)@$menu->query['id'];

		// If the menu item does not concern this newsfeed
		if ($menu && ($menu->query['option'] != 'com_dpcalendar' || $menu->query['view'] != 'event' || $id != $this->event->id)) {
			// If this is not a single event menu item, set the page title to
			// the event title
			if ($this->event->title) {
				$this->document->setTitle($this->event->title);
			}

			$path     = array(array('title' => $this->event->title, 'link' => ''));
			$category = DPCalendarHelper::getCalendar($this->event->catid);
			while ($category != null && ($menu->query['option'] != 'com_dpcalendar' || $menu->query['view'] == 'event' || $id != $category->id) &&
				$category->id > 1) {
				$path[]   = array('title' => $category->title, 'link' => DPCalendarHelperRoute::getCalendarRoute($category->id));
				$category = $category->getParent();
			}
			$path = array_reverse($path);
			foreach ($path as $item) {
				$pathway->addItem($item['title'], $item['link']);
			}
		}

		$metadesc = trim($this->event->metadesc);
		if (!$metadesc) {
			$metadesc = JHtmlString::truncate($this->event->description, 200, true, false);
		}
		if ($metadesc) {
			$this->document->setDescription($this->event->title . ' '
				. DPCalendarHelper::getDateStringFromEvent($this->event, $this->params->get('event_date_format', 'm.d.Y'),
					$this->params->get('event_time_format', 'g:i a'), true) . ' ' . $metadesc);
		}

		if ($this->event->metakey) {
			$this->document->setMetadata('keywords', $this->event->metakey);
		}

		if ($app->get('MetaAuthor') == '1' && !empty($this->event->author)) {
			$this->document->setMetaData('author', $this->event->author);
		}

		$mdata = $this->event->metadata->toArray();
		foreach ($mdata as $k => $v) {
			if ($v) {
				$this->document->setMetadata($k, $v);
			}
		}
	}
}
