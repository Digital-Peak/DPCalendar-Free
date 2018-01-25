<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2018 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
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

		JHtml::_('script', 'com_dpcalendar/iframe-resizer/iframeresizer.min.js', ['relative' => true], ['defer' => true]);
		JFactory::getDocument()->addStyleDeclaration('#general .controls {margin-left: 0}');
		JFactory::getDocument()->addScriptDeclaration("document.addEventListener('DOMContentLoaded', function () {
				iFrameResize({log: false});
	});");

		$url    = 'index.php?option=com_dpcalendar&view=extcalendars';
		$url    .= '&dpplugin=' . $this->element['plugin'];
		$url    .= '&import=' . $this->element['import'];
		$url    .= '&tmpl=component';
		$buffer = '<iframe src="' . JRoute::_($url) . '" style="width:100%; border:0"m id="' . $this->id . '"></iframe>';

		return $buffer;
	}
}
