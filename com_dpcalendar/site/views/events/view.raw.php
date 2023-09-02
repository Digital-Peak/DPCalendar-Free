<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2014 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

defined('_JEXEC') or die();

use DPCalendar\Helper\DPCalendarHelper;
use DPCalendar\View\BaseView;
use Joomla\CMS\Helper\ModuleHelper;
use Joomla\Registry\Registry;

class DPCalendarViewEvents extends BaseView
{
	public function init()
	{
		// Don't display errors as we want to send them nicely in the ajax response
		ini_set('display_errors', false);

		// Registering shutdown function to catch fatal errors
		register_shutdown_function([$this, 'handleError']);

		header("Cache-Control: no-cache");

		// Set some defaults
		$this->input->set('list.limit', 1000);
		$this->get('State')->set('filter.state', [1, 3]);
		$this->get('State')->set('filter.state_owner', true);
		$this->get('State')->set('filter.search', '');

		// Convert the dates from the user timezone into normal
		$tz    = DPCalendarHelper::getDate()->getTimezone()->getName();
		$start = $this->app->input->get('date-start');
		if ($start) {
			$start = DPCalendarHelper::getDate($start, false, $tz);
			$this->getModel()->setState('list.start-date', $start);
		}

		$end = $this->app->input->get('date-end');
		if ($end) {
			$end = DPCalendarHelper::getDate($end, false, $tz);
			$this->getModel()->setState('list.end-date', $end);
		}

		if ($id = $this->input->getString('module-id')) {
			$moduleParams = new Registry(ModuleHelper::getModuleById($id)->params);
			$this->getModel()->setStateFromParams($moduleParams);
			$this->params->merge($moduleParams);
		}

		$this->items = $this->get('Items');

		if ($this->getModel()->getError()) {
			DPCalendarHelper::sendMessage($this->getModel()->getError(), true);
			$this->app->close();
		}

		$this->compactMode = $this->input->getInt('compact', 0);
		if ($this->compactMode == 1) {
			$this->setLayout('compact');
		}
	}

	public function handleError()
	{
		// Getting last error
		$error = error_get_last();
		if ($error && ($error['type'] == E_ERROR || $error['type'] == E_USER_ERROR)) {
			ob_clean();
			echo json_encode(
				[
					[
						'data'     => [],
						'messages' => ['error' => [$error['message'] . ': <br/>' . $error['file'] . ' ' . $error['line']]]
					]
				]
			);

			// We always send ok as we want to be able to handle the error by our own
			header('Status: 200 Ok');
			header('HTTP/1.0 200 Ok');
			die();
		}
	}
}
