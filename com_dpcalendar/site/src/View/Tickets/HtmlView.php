<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2015 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Component\DPCalendar\Site\View\Tickets;

defined('_JEXEC') or die();

use DigitalPeak\Component\DPCalendar\Administrator\View\BaseView;
use Joomla\CMS\Factory;
use Joomla\CMS\Pagination\Pagination;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;

class HtmlView extends BaseView
{
	/** @var Pagination */
	protected $pagination;

	/** @var ?\stdClass */
	protected $event;

	/** @var ?object */
	protected $booking;

	/** @var array */
	protected $tickets;

	/** @var array */
	protected $eventInstances;

	/** @var string */
	protected $afterButtonEventOutput;

	public function display($tpl = null): void
	{
		$model = Factory::getApplication()->bootComponent('dpcalendar')->getMVCFactory()->createModel('Tickets', 'Administrator');
		$this->setModel($model, true);

		parent::display($tpl);
	}

	protected function init(): void
	{
		// If we don't show the event tickets, show the user tickets
		if ($this->user->guest !== 0) {
			$this->app->enqueueMessage($this->translate('COM_DPCALENDAR_NOT_LOGGED_IN'), 'warning');
			$this->app->redirect(Route::_('index.php?option=com_users&view=login&return=' . base64_encode(Uri::getInstance())));

			return;
		}

		$ordering  = 'a.name';
		$direction = 'asc';

		$orderParam = $this->params->get('tickets_order_dir', 'a.name asc');
		if (strpos((string)$orderParam, ' ')) {
			[$ordering, $direction] = explode(' ', (string)$orderParam);
		}

		$this->getModel()->setState('list.ordering', $ordering);
		$this->getModel()->setState('list.direction', $direction);
		$this->getModel()->setState('filter.my', !$this->input->getInt('e_id', 0) && !$this->input->getInt('b_id', 0));
		$this->getModel()->setState('filter.event_id', $this->input->getInt('e_id'));
		$this->getModel()->setState('filter.state', $this->params->get('tickets_states', [0, 1, 2, 3, 4, 5, 6, 7, 8, 9]));

		$this->event = $this->input->getInt('e_id', 0) !== 0 ? $this->getModel()->getEvent((string)$this->input->getInt('e_id', 0)) : null;
		if ($this->input->getInt('b_id', 0) !== 0) {
			$booking = $this->app->bootComponent('dpcalendar')->getMVCFactory()->createModel('Booking', 'Administrator')
				->getItem($this->input->getInt('b_id', 0));
			$this->booking = is_object($booking) ? $booking : null;
		}

		$this->tickets        = $this->get('Items');
		$this->pagination     = $this->get('Pagination');
		$this->eventInstances = [];

		// For button plugins
		PluginHelper::importPlugin('dpcalendar');
		$this->afterButtonEventOutput = implode(' ', $this->app->triggerEvent(
			'onDPCalendarAfterButtons',
			['tickets', $this->tickets]
		));

		// Prepare content, eg custom fields
		PluginHelper::importPlugin('content');
		/** @var \stdClass $ticket */
		foreach ($this->tickets as $ticket) {
			if (!empty($ticket->event_calid)) {
				$ticket->catid = $ticket->event_calid;
			}

			$ticket->text = '';
			$this->app->triggerEvent('onContentPrepare', ['com_dpcalendar.ticket', &$ticket, &$this->params, 0]);

			$ticket->price_label = '';
			if (!empty($ticket->event_prices)) {
				$prices = json_decode((string)$ticket->event_prices);

				if (!empty($prices->label[$ticket->type ?? ''])) {
					$ticket->price_label = $prices->label[$ticket->type ?? ''];
				}
			}

			if (array_key_exists($ticket->event_id, $this->eventInstances)) {
				continue;
			}

			$this->eventInstances[$ticket->event_id] = [];
			if ($ticket->event_original_id != -1) {
				continue;
			}

			$seriesModel = $this->app->bootComponent('dpcalendar')->getMVCFactory()->createModel('Events', 'Administrator', ['ignore_request' => true]);
			$seriesModel->getState();
			$seriesModel->setState('filter.children', $ticket->event_id);
			$seriesModel->setState('filter.expand', true);
			$seriesModel->setState('list.start-date', 0);
			$seriesModel->setState('list.limit', 100);

			$this->eventInstances[$ticket->event_id] = $seriesModel->getItems();
		}
	}
}
