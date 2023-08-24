<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2015 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

defined('_JEXEC') or die();

use DPCalendar\Helper\DateHelper;
use DPCalendar\Helper\DPCalendarHelper;
use DPCalendar\HTML\Document\HtmlDocument;
use DPCalendar\Router\Router;
use DPCalendar\Translator\Translator;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Table\Table;
use Joomla\Utilities\ArrayHelper;

class PlgContentDPCalendar extends CMSPlugin
{
	/** @var \Joomla\CMS\Application\CMSApplication */
	protected $app;

	/** @var \Joomla\Database\DatabaseDriver */
	protected $db;

	protected $autoloadLanguage = true;

	public function onContentPrepare($context, $item, $articleParams)
	{
		if (!$item->text) {
			return;
		}

		// Count how many times we need to process events
		$count = substr_count($item->text, '{{#events');
		if (!$count) {
			return true;
		}

		// Load some classes
		if (!JLoader::import('components.com_dpcalendar.helpers.dpcalendar', JPATH_ADMINISTRATOR)) {
			return true;
		}

		BaseDatabaseModel::addIncludePath(JPATH_SITE . '/components/com_dpcalendar/models', 'DPCalendarModel');
		PluginHelper::importPlugin('content');

		for ($i = 0; $i < $count; $i++) {
			// Check for parameters
			preg_match('/{{#events\s*.*?}}/i', $item->text, $starts, PREG_OFFSET_CAPTURE);
			preg_match('/{{\/events}}/i', $item->text, $ends, PREG_OFFSET_CAPTURE);

			// Extract the parameters
			$start  = $starts[0][1] + strlen($starts[0][0]);
			$end    = $ends[0][1];
			$params = explode(' ', str_replace(['{{#events', '}}'], '', $starts[0][0]));

			// Load the module
			$model = BaseDatabaseModel::getInstance('Events', 'DPCalendarModel', ['ignore_request' => true]);

			// Set some default variables
			$model->getState();
			$model->setState('filter.state', 1);
			$model->setState('filter.expand', true);
			$model->setState('list.limit', 5);

			$now = DPCalendarHelper::getDate();
			$model->setState('list.start-date', $now->format('U'));
			$now->modify('+1 year');
			$model->setState('list.end-date', $now->format('U'));

			// Loop through the params and set them on the model
			foreach ($params as $string) {
				$string = trim($string);
				if (!$string) {
					continue;
				}

				$paramKey   = null;
				$paramValue = null;
				$parts      = explode('=', $string);
				if (count($parts) > 0) {
					$paramKey = $parts[0];
				}
				if (count($parts) > 1) {
					$paramValue = $parts[1];
				}

				if ($paramKey == 'calid') {
					$paramValue = explode(',', $paramValue);
					$model->setState('category.id', $paramValue);
				}
				if ($paramKey == 'eventid') {
					$model->setState('filter.search', 'id:' . $paramValue);
					$model->setState('list.start-date', 0);
					$model->setState('list.end-date', null);
				}
				if ($paramKey == 'my') {
					$model->setState('filter.author', $paramValue == '1' ? '-1' : '0');
				}
				if ($paramKey == 'author') {
					$model->setState('filter.author', $paramValue);
				}
				if ($paramKey == 'limit') {
					$model->setState('list.limit', (int)$paramValue);
				}
				if ($paramKey == 'order') {
					$model->setState('list.ordering', $paramValue);
				}
				if ($paramKey == 'orderdir') {
					$model->setState('list.direction', $paramValue);
				}
				if ($paramKey == 'tagid') {
					$paramValue = explode(',', $paramValue);
					ArrayHelper::toInteger($paramValue);
					$model->setState('filter.tags', $paramValue);
				}
				if ($paramKey == 'featured') {
					$model->setState('filter.featured', $paramValue);
				}
				if ($paramKey == 'startdate') {
					$model->setState('list.start-date', DPCalendarHelper::getDate($paramValue));
				}
				if ($paramKey == 'enddate') {
					$model->setState('list.end-date', DPCalendarHelper::getDate($paramValue));
				}
				if ($paramKey == 'locationid') {
					$paramValue = explode(',', $paramValue);
					ArrayHelper::toInteger($paramValue);
					$model->setState('filter.locations', $paramValue);
				}
			}

			// Get the events
			$events = $model->getItems();
			foreach ($events as $index => $event) {
				// Avoid recursion
				if (isset($item->id) && $event->id == $item->id) {
					unset($events[$index]);
					continue;
				}

				$event->text = $event->description ?: '';
				$this->app->triggerEvent('onContentPrepare', ['com_dpcalendar.event', &$event, &$event->params, 0]);
				$event->description = $event->text;
			}

			// Render the output
			$output = DPCalendarHelper::renderEvents($events, '{{#events}}' . substr($item->text, $start, $end - $start) . '{{/events}}');

			// Set the output on the item
			$item->text = substr_replace($item->text, $output, $starts[0][1], $end + 11 - $starts[0][1]);
		}

		return true;
	}

	public function onContentAfterDisplay($context, $item)
	{
		$buffer = '';
		$layout = '';
		$userId = 0;

		// Check if we can render contacts
		if ($context === 'com_contact.contact' && $this->params->get('show_contact_events', 1)) {
			$layout = 'contact/events';
			$userId = $item->user_id;
		}

		// Check if we can render users
		if ($context === 'com_users.user' && $this->params->get('events_users', 1)) {
			$layout = 'users/events';
			$userId = $item->id;
		}

		if (!$userId) {
			return;
		}

		// Load some classes
		if (!JLoader::import('components.com_dpcalendar.helpers.dpcalendar', JPATH_ADMINISTRATOR)) {
			return;
		}

		$router     = new Router();
		$dateHelper = new DateHelper();
		$document   = new HtmlDocument();
		$translator = new Translator();

		if ($userId && $this->params->get('show_bookings') && Factory::getUser()->authorise('dpcalendar.admin.book', 'com_dpcalendar')) {
			Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_dpcalendar/tables');
			BaseDatabaseModel::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_dpcalendar/models');
			$this->app->getLanguage()->load('com_dpcalendar', JPATH_ADMINISTRATOR . '/components/com_dpcalendar');

			$model = BaseDatabaseModel::getInstance('Bookings', 'DPCalendarModel', ['ignore_request' => true]);
			$model->setState('filter.created_by', $userId);

			$bookings = $model->getItems();
			$params   = ComponentHelper::getParams('com_dpcalendar');

			ob_start();
			include PluginHelper::getLayoutPath('content', 'dpcalendar', 'bookings/list');
			$buffer .= ob_get_clean();
		}

		if (!$layout) {
			return $buffer;
		}

		BaseDatabaseModel::addIncludePath(JPATH_SITE . '/components/com_dpcalendar/models');

		// Load the model
		$model = BaseDatabaseModel::getInstance('Events', 'DPCalendarModel', ['ignore_request' => true]);
		$model->setState('list.start-date', 0);
		$model->setState('filter.expand', false);
		$model->setState('filter.author', $userId);
		$authoredEvents = $model->getItems();

		$model = BaseDatabaseModel::getInstance('Events', 'DPCalendarModel', ['ignore_request' => true]);
		$model->setState('list.start-date', 0);
		$model->setState('filter.expand', false);
		$model->setState('filter.hosts', $userId);
		$hostEvents = $model->getItems();

		ob_start();
		include PluginHelper::getLayoutPath('content', 'dpcalendar', $layout);
		return $buffer . ob_get_clean();
	}

	public function onContentAfterDelete($context, $item)
	{
		// Check if it is a category to delete
		if ($context != 'com_categories.category') {
			return;
		}

		// Check if the category belongs to DPCalendar
		if ($item->extension != 'com_dpcalendar') {
			return;
		}

		JLoader::import('components.com_dpcalendar.helpers.dpcalendar', JPATH_ADMINISTRATOR);

		// Add the required table and module path
		Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_dpcalendar/tables');
		BaseDatabaseModel::addIncludePath(JPATH_SITE . '/components/com_dpcalendar/models');

		// Load the model
		$model = BaseDatabaseModel::getInstance('Form', 'DPCalendarModel', ['ignore_request' => true]);

		// Select all events which do belong to the category
		$query = $this->db->getQuery(true);
		$query->select('id')->from('#__dpcalendar_events')->where('original_id in (0, -1) and catid=' . (int)$item->id);
		$this->db->setQuery($query);

		// Loop over the events
		foreach ($this->db->loadAssocList() as $eventId) {
			// We are using here the model to properly trigger the events

			// Unpublish it first
			$model->publish($eventId, -2);

			// The actually delete the event
			if (!$model->delete($eventId)) {
				// Add the error message
				$this->app->enqueueMessage($model->getError(), 'error');
			}
		}
	}

	public function onContentPrepareForm($form)
	{
		if ($form->getName() != 'com_fields.field.com_dpcalendar.event') {
			return true;
		}

		$form->loadFile(JPATH_PLUGINS . '/content/dpcalendar/forms/tickets.xml');

		return true;
	}
}
