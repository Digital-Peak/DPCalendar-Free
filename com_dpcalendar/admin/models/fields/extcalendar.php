<?php
/**
 * @package   DPCalendar
 * @author    Digital Peak http://www.digital-peak.com
 * @copyright Copyright (C) 2007 - 2020 Digital Peak. All rights reserved.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */
defined('_JEXEC') or die();

JLoader::import('components.com_dpcalendar.helpers.dpcalendar', JPATH_ADMINISTRATOR);

if (!class_exists('DPCalendarHelper')) {
	return;
}

class JFormFieldExtcalendar extends JFormField
{
	protected $type = 'Extcalendar';

	public function getInput()
	{
		JFactory::getSession()->set('extcalendarOrigin', JUri::getInstance()->toString(), 'DPCalendar');

		(new \DPCalendar\HTML\Document\HtmlDocument())->loadScriptFile('dpcalendar/fields/extcalendar.js');
		JFactory::getDocument()->addStyleDeclaration('#general .controls {margin-left: 0}');

		$url    = 'index.php?option=com_dpcalendar&view=extcalendars';
		$url    .= '&dpplugin=' . $this->element['plugin'];
		$url    .= '&import=' . $this->element['import'];
		$url    .= '&tmpl=component';
		$buffer = '<iframe src="' . JRoute::_($url) . '" style="width:100%; border:0"m id="' . $this->id . '" name="' . $this->id . '"></iframe>';

		return $buffer;
	}
}
