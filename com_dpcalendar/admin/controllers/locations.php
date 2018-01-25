<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2018 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */
defined('_JEXEC') or die();

JLoader::import('joomla.application.component.controlleradmin');

class DPCalendarControllerLocations extends JControllerAdmin
{

	protected $text_prefix = 'COM_DPCALENDAR_LOCATION';

	public function getModel ($name = 'Location', $prefix = 'DPCalendarModel', $config = array('ignore_request' => true))
	{
		$model = parent::getModel($name, $prefix, $config);
		return $model;
	}

	public function publish ()
	{
		$return = parent::publish();

		if (JFactory::getApplication()->input->getVar('ajax') != 0)
		{
			$text = JText::plural($this->text_prefix . '_N_ITEMS_TRASHED', count(JFactory::getApplication()->input->get('cid', array(), 'array')));
			if ($this->message == $text)
			{
				DPCalendarHelper::sendMessage($this->message, false);
			}
			else
			{
				DPCalendarHelper::sendMessage($this->message, true);
			}
		}
		return $return;
	}
}
