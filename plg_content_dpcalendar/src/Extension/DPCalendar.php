<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2015 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Plugin\Content\DPCalendar\Extension;

\defined('_JEXEC') or die();

use DigitalPeak\Component\DPCalendar\Administrator\Helper\DateHelper;
use DigitalPeak\Component\DPCalendar\Administrator\Helper\DPCalendarHelper;
use DigitalPeak\Component\DPCalendar\Administrator\HTML\Document\HtmlDocument;
use DigitalPeak\Component\DPCalendar\Administrator\Model\BookingsModel;
use DigitalPeak\Component\DPCalendar\Administrator\Router\Router;
use DigitalPeak\Component\DPCalendar\Administrator\Translator\Translator;
use DigitalPeak\Component\DPCalendar\Site\Model\EventsModel;
use DigitalPeak\Component\DPCalendar\Site\Model\FormModel;
use Joomla\CMS\Application\CMSApplicationInterface;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Registry\Registry;
use Joomla\Utilities\ArrayHelper;

class DPCalendar extends CMSPlugin
{
	use DatabaseAwareTrait;

	protected $autoloadLanguage = true;

	public function onContentPrepare(string $context, mixed $item): bool
	{
		if (!$item->text) {
			return true;
		}

		$app = $this->getApplication();
		if (!$app instanceof CMSApplicationInterface) {
			return true;
		}

		$component = $app->bootComponent('dpcalendar');

		$content = (string)$item->text;

		// Get the {{#events}} tag
		preg_match_all('/{{#events\s*([^}]*)}}/', $content, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE);

		// Looping through the matches in reverse order, so we do not replace the offset for replacement
		foreach (array_reverse($matches) as $match) {
			// Extract individual key-value pairs
			preg_match_all('/(\w+)=["\']([^"\']*)["\']|(\w+)=([^"\s]+)/', $match[1][0], $params, PREG_SET_ORDER);

			/** @var EventsModel $model */
			$model = $component->getMVCFactory()->createModel('Events', 'Site', ['ignore_request' => true]);

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
			foreach ($params as $kv) {
				$paramValue = $kv[\count($kv) - 1];
				switch ($kv[\count($kv) - 2]) {
					case 'calid':
						$model->setState('category.id', explode(',', $paramValue));
						break;
					case 'eventid':
						$model->setState('filter.search', 'id:' . $paramValue);
						$model->setState('list.start-date', 0);
						$model->setState('list.end-date', null);
						break;
					case 'my':
						$model->setState('filter.author', $paramValue === '1' ? '-1' : '0');
						break;
					case 'author':
						$model->setState('filter.author', $paramValue);
						break;
					case 'limit':
						$model->setState('list.limit', (int)$paramValue);
						break;
					case 'order':
						$model->setState('list.ordering', $paramValue);
						break;
					case 'orderdir':
						$model->setState('list.direction', $paramValue);
						break;
					case 'tagid':
						$model->setState('filter.tags', ArrayHelper::toInteger(explode(',', $paramValue)));
						break;
					case 'featured':
						$model->setState('filter.featured', $paramValue);
						break;
					case 'startdate':
						try {
							$model->setState('list.start-date', DPCalendarHelper::getDate($paramValue));
						} catch (\Exception) {
						}
						break;
					case 'enddate':
						try {
							$model->setState('list.end-date', DPCalendarHelper::getDate($paramValue));
						} catch (\Exception) {
						}
						break;
					case 'locationid':
						$model->setState('filter.locations', ArrayHelper::toInteger(explode(',', $paramValue)));
						break;
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
				$app->triggerEvent('onContentPrepare', ['com_dpcalendar.event', &$event, &$event->params, 0]);
				$event->description = $event->text;
			}
			$params = new Registry(ComponentHelper::getParams('com_dpcalendar'));
			$params->set('description_length', 0);

			// The start is the position of the match plus its length
			$start = $match[0][1] + \strlen($match[0][0]);

			// The end is the position of the closing tag
			$end = strpos($content, '{{/events}}', $start);

			// Check if there is an else block
			if (substr($content, $end + 11, 11) === '{{^events}}') {
				// Reset the end to the end position of the else block
				$end = strpos($content, '{{/events}}', $end + 11);
			}

			// Render the output while removing the arguments
			$output = DPCalendarHelper::renderEvents($events, '{{#events}}' . substr($content, $start, $end - $start) . '{{/events}}', $params);

			// Replace the tag with the generated output
			$content = substr_replace($content, $output, $match[0][1], $end + 11 - $match[0][1]);
		}

		// Set the new content
		$item->text = $content;

		return true;
	}

	public function onContentAfterDisplay(string $context, mixed $item): string
	{
		$app = $this->getApplication();
		if (!$app instanceof CMSApplicationInterface) {
			return '';
		}

		$component = $app->bootComponent('dpcalendar');

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
			return '';
		}

		$user       = $app->getIdentity();
		$router     = new Router();
		$dateHelper = new DateHelper();
		$document   = new HtmlDocument();
		$translator = new Translator();

		if ($this->params->get('show_bookings', '0') == '1' && $user && $user->authorise('dpcalendar.admin.book', 'com_dpcalendar')) {
			$app->getLanguage()->load('com_dpcalendar', JPATH_ADMINISTRATOR . '/components/com_dpcalendar');

			/** @var BookingsModel $model */
			$model = $component->getMVCFactory()->createModel('Bookings', 'Administrator', ['ignore_request' => true]);
			$model->setState('filter.created_by', $userId);

			$bookings = $model->getItems();
			$params   = ComponentHelper::getParams('com_dpcalendar');

			ob_start();
			include PluginHelper::getLayoutPath('content', 'dpcalendar', 'bookings/list');
			$buffer .= ob_get_clean();
		}

		if ($layout === '') {
			return $buffer;
		}

		/** @var EventsModel $model */
		$model = $component->getMVCFactory()->createModel('Events', 'Site', ['ignore_request' => true]);
		$model->setState('list.start-date', 0);
		$model->setState('filter.expand', false);
		$model->setState('filter.author', $userId);

		$authoredEvents = $model->getItems();

		/** @var EventsModel $model */
		$model = $component->getMVCFactory()->createModel('Events', 'Site', ['ignore_request' => true]);
		$model->setState('list.start-date', 0);
		$model->setState('filter.expand', false);
		$model->setState('filter.hosts', $userId);

		$hostEvents = $model->getItems();

		ob_start();
		include PluginHelper::getLayoutPath('content', 'dpcalendar', $layout);
		return $buffer . ob_get_clean();
	}

	public function onContentAfterDelete(string $context, mixed $item): void
	{
		// Check if it is a category to delete
		if ($context !== 'com_categories.category') {
			return;
		}

		// Check if the category belongs to DPCalendar
		if ($item->extension != 'com_dpcalendar') {
			return;
		}

		$app = $this->getApplication();
		if (!$app instanceof CMSApplicationInterface) {
			return;
		}

		/** @var FormModel $model */
		$model = $app->bootComponent('dpcalendar')->getMVCFactory()->createModel('Form', 'Site', ['ignore_request' => true]);

		// Select all events which do belong to the category
		$query = $this->getDatabase()->getQuery(true);
		$query->select('id')->from('#__dpcalendar_events')->where('original_id in (0, -1) and catid=' . (int)$item->id);
		$this->getDatabase()->setQuery($query);

		// Loop over the events
		foreach ($this->getDatabase()->loadAssocList() as $eventId) {
			// We are using here the model to properly trigger the events

			// Unpublish it first
			$model->publish($eventId, -2);

			// The actually delete the event
			if (!$model->delete($eventId)) {
				// Add the error message
				$app->enqueueMessage($model->getError(), 'error');
			}
		}
	}

	public function onContentPrepareForm(Form $form): bool
	{
		if ($form->getName() !== 'com_fields.field.com_dpcalendar.event') {
			return true;
		}

		$form->loadFile(JPATH_PLUGINS . '/content/dpcalendar/forms/tickets.xml');

		return true;
	}
}
