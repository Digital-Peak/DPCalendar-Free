<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2014 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Component\DPCalendar\Site\View\Events;

\defined('_JEXEC') or die();

use DigitalPeak\Component\DPCalendar\Administrator\Helper\DPCalendarHelper;
use DigitalPeak\Component\DPCalendar\Administrator\View\BaseView;
use Joomla\CMS\Application\SiteApplication;
use Joomla\CMS\Factory;
use Joomla\CMS\Helper\ModuleHelper;
use Joomla\Registry\Registry;

class RawView extends BaseView
{
	protected array $items = [];

	protected bool $compactMode = false;

	public function display($tpl = null): void
	{
		/** @var SiteApplication $app */
		$app = Factory::getApplication();

		// Add the models
		$module = $app->getInput()->getInt('module_id', 0);
		$model  = $app->bootComponent('dpcalendar')->getMVCFactory()->createModel(
			'Events',
			'Site',
			// Set the name for the state from the current view or module
			['name' => ($app->getMenu()->getActive()?->query['view'] ?? 'calendar') . '.'
				. ($module !== 0 ? 'module.' . $module : $app->getInput()->getInt('Itemid', 0))]
		);
		$this->setModel($model, true);

		parent::display($tpl);
	}

	protected function init(): void
	{
		// Don't display errors as we want to send them nicely in the ajax response
		ini_set('display_errors', 'false');

		// Registering shutdown function to catch fatal errors
		register_shutdown_function([$this, 'handleError']);

		$this->app->setHeader('Cache-Control', 'no-cache');

		$model = $this->getModel();

		// Set some defaults
		$this->input->set('list.limit', 1000);
		$this->state->set('filter.state', [1, 3]);
		$this->state->set('filter.state_owner', true);

		// Convert the dates from the user timezone into normal
		$tz    = DPCalendarHelper::getDate()->getTimezone()->getName();
		$start = $this->app->getInput()->get('date-start', $model->getState('list.start-date'));
		if ($start) {
			$start = DPCalendarHelper::getDate($start, false, $tz);
			$model->setState('list.start-date', $start);
		}

		$end = $this->app->getInput()->get('date-end', $model->getState('list.end-date'));
		if ($end) {
			$end = DPCalendarHelper::getDate($end, false, $tz);
			$model->setState('list.end-date', $end);
		}

		$id = $this->input->getString('module_id', '');
		if ($id !== '' && $id !== '0') {
			$moduleParams = new Registry(ModuleHelper::getModuleById($id)->params);
			$model->setStateFromParams($moduleParams);
			$this->params->merge($moduleParams);

			if ($moduleParams->get('compact_events', 2) == 1) {
				$this->setLayout('compact');
			}

			$this->compactMode = $moduleParams->get('compact_events', 2) != '0';

			// Author state must be set explicit, otherwise it inherits from global config
			$model->setState('filter.author', $moduleParams->get('calendar_filter_author', 0));
		}

		// Set the calendars
		$model->setState('filter.calendars', array_filter($this->state->get('filter.calendars', []), fn ($c) => !empty($c) && $c !== -2));
		$model->setState('category.id', $this->state->get('filter.calendars', []));

		if ($location = $model->getState('filter.location')) {
			$model->setState(
				'filter.location',
				$this->getDPCalendar()->getMVCFactory()->createModel('Geo', 'Administrator')->getLocation($location, false)
			);
		}

		$this->items = $this->getModel()->getItems();
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
