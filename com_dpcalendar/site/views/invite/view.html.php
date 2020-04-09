<?php
/**
 * @package   DPCalendar
 * @author    Digital Peak http://www.digital-peak.com
 * @copyright Copyright (C) 2007 - 2020 Digital Peak. All rights reserved.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */
defined('_JEXEC') or die();

class DPCalendarViewInvite extends \DPCalendar\View\BaseView
{
	public function init()
	{
		// Set the default model
		$this->setModel(JModelLegacy::getInstance('Event', 'DPCalendarModel'), true);

		JFactory::getLanguage()->load('', JPATH_ADMINISTRATOR);

		$event = JModelLegacy::getInstance('Event', 'DPCalendarModel')->getItem($this->input->getInt('id'));
		if (!$event || !$event->id || $this->user->authorise('dpcalendar.invite', 'com_dpcalendar.category.' . $event->catid) !== true) {
			throw new Exception(JText::_('JERROR_ALERTNOAUTHOR'));
		}

		JForm::addFormPath(JPATH_ADMINISTRATOR . '/components/com_dpcalendar/models/forms');
		JForm::addFieldPath(JPATH_ADMINISTRATOR . '/components/com_dpcalendar/models/fields');

		$this->form = JForm::getInstance('com_dpcalendar.invite', 'invite', ['control' => 'jform']);

		$this->form->setValue('event_id', null, $event->id);

		JHtml::_('behavior.formvalidator');
	}
}
