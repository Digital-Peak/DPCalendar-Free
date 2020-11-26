<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2014 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

defined('_JEXEC') or die();

use Joomla\Utilities\ArrayHelper;

JLoader::import('joomla.application.component.controlleradmin');

class DPCalendarControllerEvents extends JControllerAdmin
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

	public function csvexport()
	{
		JSession::checkToken() or jexit(JText::_('JINVALID_TOKEN'));

		\JModelLegacy::addIncludePath(JPATH_SITE . '/components/com_dpcalendar/models');

		$fields                   = [];
		$fields['id']             = JText::_('JGRID_HEADING_ID');
		$fields['title']          = JText::_('JGLOBAL_TITLE');
		$fields['calendar']       = JText::_('COM_DPCALENDAR_CALENDAR');
		$fields['color']          = JText::_('COM_DPCALENDAR_FIELD_COLOR_LABEL');
		$fields['url']            = JText::_('COM_DPCALENDAR_FIELD_URL_LABEL');
		$fields['start_date']     = JText::_('COM_DPCALENDAR_FIELD_START_DATE_LABEL');
		$fields['end_date']       = JText::_('COM_DPCALENDAR_FIELD_END_DATE_LABEL');
		$fields['all_day']        = JText::_('COM_DPCALENDAR_FIELD_ALL_DAY_LABEL');
		$fields['rrule']          = JText::_('COM_DPCALENDAR_FIELD_SCHEDULING_RRULE_LABEL');
		$fields['description']    = JText::_('JGLOBAL_DESCRIPTION');
		$fields['locations']      = JText::_('COM_DPCALENDAR_LOCATIONS');
		$fields['alias']          = JText::_('JFIELD_ALIAS_LABEL');
		$fields['featured']       = JText::_('JFEATURED');
		$fields['status']         = JText::_('JSTATUS');
		$fields['access']         = JText::_('JFIELD_ACCESS_LABEL');
		$fields['access_content'] = JText::_('COM_DPCALENDAR_FIELD_ACCESS_CONTENT_LABEL');
		$fields['language']       = JText::_('JFIELD_LANGUAGE_LABEL');
		$fields['created']        = JText::_('JGLOBAL_FIELD_CREATED_LABEL');
		$fields['created_by']     = JText::_('JGLOBAL_FIELD_CREATED_BY_LABEL');
		$fields['modified']       = JText::_('JGLOBAL_FIELD_MODIFIED_LABEL');
		$fields['modified_by']    = JText::_('JGLOBAL_FIELD_MODIFIED_BY_LABEL');
		$fields['uid']            = JText::_('COM_DPCALENDAR_UID');
		$fields['timezone']       = JText::_('COM_DPCALENDAR_TIMEZONE');

		$parser = function ($name, $event) {
			switch ($name) {
				case 'calendar':
					return \DPCalendar\Helper\DPCalendarHelper::getCalendar($event->catid)->title;
				case 'status':
					return \DPCalendar\Helper\Booking::getStatusLabel($event);
				case 'locations':
					if (empty($event->locations)) {
						return '';
					}

					return \DPCalendar\Helper\Location::format($event->locations);
				case 'start_date':
				case 'end_date':
					return \DPCalendar\Helper\DPCalendarHelper::getDate($event->$name)->format($event->all_day ? 'Y-m-d' : 'Y-m-d H:i:s', true);
				case 'created':
				case 'modified':
					if ($event->$name == '0000-00-00 00:00:00') {
						return '';
					}

					return \DPCalendar\Helper\DPCalendarHelper::getDate($event->$name)->format('Y-m-d H:i:s', true);
				case 'timezone':
					return \DPCalendar\Helper\DPCalendarHelper::getDate()->getTimezone()->getName();
				default:
					return $event->$name;
			}
		};
		DPCalendarHelper::exportCsv('adminevent', $fields, $parser);
	}

	public function featured()
	{
		// Check for request forgeries
		JSession::checkToken() or jexit(JText::_('JINVALID_TOKEN'));

		$user   = JFactory::getUser();
		$ids    = $this->input->get('cid', [], 'array');
		$values = [
			'featured'   => 1,
			'unfeatured' => 0
		];
		$task   = $this->getTask();
		$value  = ArrayHelper::getValue($values, $task, 0, 'int');
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
				JFactory::getApplication()->enqueueMessage(JText::_('JLIB_APPLICATION_ERROR_EDITSTATE_NOT_PERMITTED'), 'warning');
			}
		}

		if (empty($ids)) {
			JFactory::getApplication()->enqueueMessage(JText::_('JERROR_NO_ITEMS_SELECTED'), 'warning');
		} else {
			// Get the model.
			$model = $this->getModel();

			// Publish the items.
			if (!$model->featured($ids, $value)) {
				JFactory::getApplication()->enqueueMessage($model->getError(), 'warning');
			}
		}

		$this->setRedirect('index.php?option=com_dpcalendar&view=events');
	}
}
