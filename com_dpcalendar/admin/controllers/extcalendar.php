<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2014 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */
defined('_JEXEC') or die();

JLoader::import('joomla.application.component.controllerform');

class DPCalendarControllerExtcalendar extends JControllerForm
{
	protected $text_prefix = 'COM_DPCALENDAR_EXTCALENDAR';

	protected function getRedirectToItemAppend($recordId = null, $urlVar = 'id')
	{
		$append = parent::getRedirectToItemAppend($recordId, $urlVar);

		$tmp = JFactory::getApplication()->input->get('dpplugin');
		if ($tmp) {
			$append .= '&dpplugin=' . $tmp;
		}

		return $append;
	}

	protected function getRedirectToListAppend()
	{
		$append = parent::getRedirectToListAppend();

		$tmp = JFactory::getApplication()->input->get('dpplugin');
		if ($tmp) {
			$append .= '&dpplugin=' . $tmp;
		}

		return $append;
	}
}
