<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2018 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */

defined('_JEXEC') or die();

use Joomla\Utilities\ArrayHelper;

JLoader::import('joomla.application.component.controlleradmin');

class DPCalendarControllerEvents extends JControllerAdmin
{

	public function __construct ($config = array())
	{
		parent::__construct($config);
		$this->registerTask('unfeatured', 'featured');
	}

	public function getModel ($name = 'AdminEvent', $prefix = 'DPCalendarModel', $config = array('ignore_request' => true))
	{
		$model = parent::getModel($name, $prefix, $config);
		return $model;
	}

	public function featured ()
	{
		// Check for request forgeries
		JSession::checkToken() or jexit(JText::_('JINVALID_TOKEN'));

		$user = JFactory::getUser();
		$ids = $this->input->get('cid', array(), 'array');
		$values = array(
				'featured' => 1,
				'unfeatured' => 0
		);
		$task = $this->getTask();
		$value = ArrayHelper::getValue($values, $task, 0, 'int');
		ArrayHelper::toInteger($ids);

		$this->getModel()
			->getDbo()
			->setQuery('select id, catid from #__dpcalendar_events where id in (' . implode(',', $ids) . ')');
		$events = $this->getModel()
			->getDbo()
			->loadObjectList();

		// Access checks.
		foreach ($events as $i => $event)
		{
			if (! $user->authorise('core.edit.state', 'com_dpcalendar.category.' . (int) $event->catid))
			{
				// Prune items that you can't change.
				unset($ids[$i]);
				JFactory::getApplication()->enqueueMessage(JText::_('JLIB_APPLICATION_ERROR_EDITSTATE_NOT_PERMITTED'), 'warning');
			}
		}

		if (empty($ids))
		{
			JFactory::getApplication()->enqueueMessage(JText::_('JERROR_NO_ITEMS_SELECTED'), 'warning');
		}
		else
		{
			// Get the model.
			$model = $this->getModel();

			// Publish the items.
			if (! $model->featured($ids, $value))
			{
				JFactory::getApplication()->enqueueMessage($model->getError(), 'warning');
			}
		}

		$this->setRedirect('index.php?option=com_dpcalendar&view=events');
	}
}
