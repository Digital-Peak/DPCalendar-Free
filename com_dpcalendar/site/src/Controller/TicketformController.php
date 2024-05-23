<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2015 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Component\DPCalendar\Site\Controller;

defined('_JEXEC') or die();

use DigitalPeak\Component\DPCalendar\Administrator\Controller\TicketController;
use DigitalPeak\Component\DPCalendar\Site\Helper\RouteHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;

class TicketformController extends TicketController
{
	protected $view_item = 'ticketform';
	protected $view_list = 'calendar';
	protected $option    = 'com_dpcalendar';

	private bool $ignoreCaptcha = false;

	protected function allowAdd($data = []): bool
	{
		return false;
	}

	protected function allowEdit($data = [], $key = 'id')
	{
		$recordId = $data[$key] ?? 0;
		$ticket   = $this->getModel('Ticket')->getItem($recordId);

		if (empty($ticket)) {
			return false;
		}

		return $ticket->params->get('access-edit');
	}

	protected function allowDelete(array $data = [], string $key = 't_id'): bool
	{
		$recordId = $data[$key] ?? 0;
		$ticket   = $this->getModel('Ticket')->getItem($recordId);

		if (empty($ticket)) {
			return false;
		}

		return (bool)$ticket->params->get('access-delete');
	}

	public function edit($key = 'id', $urlVar = 't_id')
	{
		$this->input->set('layout', 'edit');

		return parent::edit($key, $urlVar);
	}

	public function cancel($key = 't_id')
	{
		$success = parent::cancel($key);

		// Redirect to the return page.
		$this->setRedirect($this->getReturnPage());

		return $success;
	}

	public function delete(string $key = 't_id'): bool
	{
		$recordId = $this->input->getInt($key, 0);

		if (!$this->allowDelete([$key => $recordId], $key)) {
			throw new \Exception(Text::_('JLIB_APPLICATION_ERROR_EDIT_NOT_PERMITTED'));
		}

		$ticket = $this->getModel('Ticket')->getItem($recordId);
		if (!$ticket || !$this->getModel('Ticket')->delete($recordId)) {
			throw new \Exception(Text::_('JLIB_APPLICATION_ERROR_EDIT_NOT_PERMITTED'));
		}

		// Redirect to the return page
		$event = $this->app->bootComponent('dpcalendar')->getMVCFactory()->createTable('Event', 'Administrator');
		$event->load($ticket->event_id);
		$this->setRedirect(RouteHelper::getEventRoute($event->id, $event->catid), Text::sprintf('COM_DPCALENDAR_TICKET_N_ITEMS_DELETED_1', 1));

		return true;
	}

	public function save($key = null, $urlVar = 't_id')
	{
		$result = parent::save($key, $urlVar);

		if ($return = $this->input->get('return', null, 'base64')) {
			$this->setRedirect(base64_decode((string)$return));
		}

		return $result;
	}

	public function getModel($name = 'Ticket', $prefix = 'Administrator', $config = ['ignore_request' => true])
	{
		$model = parent::getModel($name, $prefix, $config);

		if ($this->getTask() == 'saveall') {
			$model->setState('captcha.disabled', $this->ignoreCaptcha);
		}

		return $model;
	}

	protected function getRedirectToItemAppend($recordId = null, $urlVar = 't_id'): string
	{
		$append = parent::getRedirectToItemAppend($recordId, $urlVar);
		$itemId = $this->input->getInt('Itemid', 0);
		$return = $this->getReturnPage();

		if ($itemId !== 0) {
			$append .= '&Itemid=' . $itemId;
		}

		if ($return !== '' && $return !== '0') {
			$append .= '&return=' . base64_encode($return);
		}

		$append .= '&t_id=' . $this->input->getInt('t_id', 0);
		if ($this->input->get('tmpl', '') !== '') {
			$append .= '&tmpl=' . $this->input->get('tmpl', '');
		}

		return $append;
	}

	protected function getReturnPage(): string
	{
		$return = $this->input->getBase64('return');
		if (empty($return) || !Uri::isInternal(base64_decode($return))) {
			return Uri::base();
		}

		return base64_decode($return);
	}
}
