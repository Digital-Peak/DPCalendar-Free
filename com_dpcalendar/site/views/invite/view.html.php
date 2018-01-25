<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2018 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */
defined('_JEXEC') or die();


class DPCalendarViewInvite extends \DPCalendar\View\BaseView
{

	public function init()
	{
		JFactory::getLanguage()->load('', JPATH_ADMINISTRATOR);

		if (JFactory::getUser()->authorise('dpcalendar.invite', 'com_dpcalendar') !== true)
		{
			$this->setError(JText::_('JERROR_ALERTNOAUTHOR'));
			return false;
		}

		JForm::addFormPath(JPATH_ADMINISTRATOR . '/components/com_dpcalendar/models/forms');
		JForm::addFieldPath(JPATH_ADMINISTRATOR . '/components/com_dpcalendar/models/fields');

		$this->form = JForm::getInstance('com_dpcalendar.invite', 'invite', array(
				'control' => 'jform'
		));

		$this->form->setValue('event_id', null, JFactory::getApplication()->input->getInt('id'));
	}
}
