<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2017 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */
defined('_JEXEC') or die();

class JFormFieldDpcolor extends JFormFieldText
{

	protected $type = 'Dpcolor';

	public function getInput ()
	{
		JHtml::_('script', 'com_dpcalendar/jscolor/jscolor.js', false, true);
		return parent::getInput();
	}

	public function setup (SimpleXMLElement $element, $value, $group = null)
	{
		$element['class'] = $element['class'] . ' color' . ($element['required'] ? '' : ' {required:false}');
		$return = parent::setup($element, $value, $group);
		return $return;
	}
}
