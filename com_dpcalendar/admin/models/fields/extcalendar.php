<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2017 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */
defined('_JEXEC') or die();

JLoader::import('components.com_dpcalendar.helpers.dpcalendar', JPATH_ADMINISTRATOR);

if (!class_exists('DPCalendarHelper'))
{
	return;
}

class JFormFieldExtcalendar extends JFormField
{

	protected $type = 'Extcalendar';

	public function getInput()
	{
		DPCalendarHelper::loadLibrary(array(
				'jquery' => true
		));
		JFactory::getSession()->set('extcalendarOrigin', JUri::getInstance()->toString(), 'DPCalendar');

		JHtml::_('script', 'com_dpcalendar/iframe-resizer/jquery.iframeResizer.min.js', false, true);
		JFactory::getDocument()->addStyleDeclaration('#general .controls {margin-left: 0}');
		JFactory::getDocument()->addScriptDeclaration("jQuery(document).ready(function() {
				jQuery('iframe').iFrameResize({log: true});
	});");

		$url = 'index.php?option=com_dpcalendar&view=extcalendars';
		$url .= '&dpplugin=' . $this->element['plugin'];
		$url .= '&import=' . $this->element['import'];
		$url .= '&tmpl=component';
		$buffer = '<iframe src="' . JRoute::_($url) . '" style="width:100%; border:0"></iframe>';
		return $buffer;
	}
}
