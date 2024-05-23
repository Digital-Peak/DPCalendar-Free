<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2015 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Component\DPCalendar\Site\View\Bookings;

defined('_JEXEC') or die();

use DigitalPeak\Component\DPCalendar\Administrator\View\BaseView;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;

class HtmlView extends BaseView
{
	/** @var string */
	protected $pagination;

	/** @var string */
	protected $bookings;

	/** @var string */
	protected $afterButtonEventOutput;

	public function display($tpl = null): void
	{
		$this->setModel(Factory::getApplication()->bootComponent('dpcalendar')->getMVCFactory()->createModel('Bookings', 'Administrator'), true);
		$this->setModel(Factory::getApplication()->bootComponent('dpcalendar')->getMVCFactory()->createModel('Booking', 'Administrator'), false);

		parent::display($tpl);
	}

	protected function init(): void
	{
		if ($this->user->guest !== 0) {
			$this->app->enqueueMessage($this->translate('COM_DPCALENDAR_NOT_LOGGED_IN'), 'warning');
			$this->app->redirect(Route::_('index.php?option=com_users&view=login&return=' . base64_encode(Uri::getInstance())));

			return;
		}

		$this->getModel()->getState();

		$ordering  = 'a.name';
		$direction = 'asc';

		$orderParam = $this->params->get('bookings_order_dir', 'a.name asc');
		if (strpos((string)$orderParam, ' ')) {
			[$ordering, $direction] = explode(' ', (string)$orderParam);
		}

		$this->getModel()->setState('list.ordering', $ordering);
		$this->getModel()->setState('list.direction', $direction);

		$event = null;

		// If we don't show the event bookings, show the user bookings
		if ($this->input->getInt('e_id', 0) === 0) {
			$this->getModel()->setState('filter.my', true);
		} else {
			$this->getModel()->setState('filter.event_id', $this->input->getInt('e_id', 0));
			$event = $this->getModel('Booking')->getEvent((string)$this->input->getInt('e_id', 0));
		}

		$this->bookings = $this->get('Items');

		// For button plugins
		PluginHelper::importPlugin('dpcalendar');
		$this->afterButtonEventOutput = implode(' ', $this->app->triggerEvent(
			'onDPCalendarAfterButtons',
			['bookings', $this->bookings]
		));

		// Prepare content, eg custom fields
		PluginHelper::importPlugin('content');
		foreach ($this->bookings as $booking) {
			if ($event && $event->catid) {
				$booking->catid = $event->catid;
			}

			$booking->text = '';
			$this->app->triggerEvent('onContentPrepare', ['com_dpcalendar.booking', &$booking, &$this->params, 0]);
		}

		$this->pagination = $this->get('Pagination');

		parent::init();
	}
}
