<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2014 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Component\DPCalendar\Site\View\Events;

\defined('_JEXEC') or die();

use DigitalPeak\Component\DPCalendar\Administrator\View\BaseView;
use Joomla\CMS\Helper\ModuleHelper;
use Joomla\Registry\Registry;

class HtmlView extends BaseView
{
	protected array $items;

	protected bool $compactMode = false;

	protected function init(): void
	{
		// Don't display errors as we want to send them nicely in the ajax response
		ini_set('display_errors', 'false');

		// Registering shutdown function to catch fatal errors
		register_shutdown_function([$this, 'handleError']);

		// Set some defaults
		$this->input->set('list.limit', 1000);
		$this->getModel()->getState()->set('filter.state', [1, 3]);

		if (($id = $this->input->getInt('module_id', 0)) !== 0) {
			foreach (ModuleHelper::getModuleList() as $module) {
				if ($id != $module->id) {
					continue;
				}

				$this->getModel()->setStateFromParams(new Registry($module->params));
				break;
			}
		}

		$this->items = $this->getModel()->getItems();

		$this->compactMode = $this->getLayout() === 'compact';
	}

	protected function handleError(): void
	{
		// Getting last error
		$error = error_get_last();
		if ($error && ($error['type'] == E_ERROR || $error['type'] == E_USER_ERROR)) {
			ob_clean();
			echo json_encode(
				[
					[
						'data'     => [],
						'messages' => [
							'error' => [
								$error['message'] . ': <br/>' . $error['file'] . ' ' . $error['line']
							]
						]
					]
				]
			);

			// We always send ok as we want to be able to handle the error by
			// our own
			header('Status: 200 Ok');
			header('HTTP/1.0 200 Ok');
			die();
		}
	}
}
