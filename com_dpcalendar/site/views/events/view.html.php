<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2018 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */

defined('_JEXEC') or die();

use Joomla\Registry\Registry;

class DPCalendarViewEvents extends \DPCalendar\View\BaseView
{

	public function init()
	{
		// Don't display errors as we want to send them nicely in the ajax response
		ini_set('display_errors', false);

		// Registering shutdown function to catch fatal errors
		register_shutdown_function(array($this, 'handleError'));

		// Set some defaults
		$this->input->set('list.limit', 1000);
		$this->get('State')->set('filter.state', 1);

		if ($id = $this->input->getInt('module-id')) {
			foreach (JModuleHelper::getModuleList() as $module) {
				if ($id != $module->id) {
					continue;
				}

				$this->getModel()->setStateFromParams(new Registry($module->params));
				break;
			}
		}

		$this->items = $this->get('Items');

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
				array(
					array(
						'data'     => array(),
						'messages' => array(
							'error' => array(
								$error['message'] . ': <br/>' . $error['file'] . ' ' . $error['line']
							)
						)
					)
				));

			// We always send ok as we want to be able to handle the error by
			// our own
			header('Status: 200 Ok');
			header('HTTP/1.0 200 Ok');
			die();
		}
	}
}
