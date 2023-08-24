<?php

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\AdminController;
use Joomla\CMS\Session\Session;

/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2014 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

defined('_JEXEC') or die();

use Joomla\Utilities\ArrayHelper;

JLoader::import('joomla.application.component.controlleradmin');

class DPCalendarControllerEvents extends AdminController
{
	public function __construct($config = [])
	{
		parent::__construct($config);
		$this->registerTask('unfeatured', 'featured');
	}

	public function getModel($name = 'AdminEvent', $prefix = 'DPCalendarModel', $config = ['ignore_request' => true])
	{
		$model = parent::getModel($name, $prefix, $config);

		return $model;
	}

	public function featured()
	{
		// Check for request forgeries
		Session::checkToken() or jexit(Text::_('JINVALID_TOKEN'));

		$user   = Factory::getUser();
		$ids    = $this->input->get('cid', [], 'array');
		$values = [
			'featured'   => 1,
			'unfeatured' => 0
		];
		$task  = $this->getTask();
		$value = ArrayHelper::getValue($values, $task, 0, 'int');
		ArrayHelper::toInteger($ids);

		$this->getModel()
			->getDbo()
			->setQuery('select id, catid from #__dpcalendar_events where id in (' . implode(',', $ids) . ')');
		$events = $this->getModel()
			->getDbo()
			->loadObjectList();

		// Access checks.
		foreach ($events as $i => $event) {
			if (!$user->authorise('core.edit.state', 'com_dpcalendar.category.' . (int)$event->catid)) {
				// Prune items that you can't change.
				unset($ids[$i]);
				Factory::getApplication()->enqueueMessage(Text::_('JLIB_APPLICATION_ERROR_EDITSTATE_NOT_PERMITTED'), 'warning');
			}
		}

		if (empty($ids)) {
			Factory::getApplication()->enqueueMessage(Text::_('JERROR_NO_ITEMS_SELECTED'), 'warning');
		} else {
			// Get the model.
			$model = $this->getModel();

			// Publish the items.
			if (!$model->featured($ids, $value)) {
				Factory::getApplication()->enqueueMessage($model->getError(), 'warning');
			}
		}

		$this->setRedirect('index.php?option=com_dpcalendar&view=events');
	}
}
