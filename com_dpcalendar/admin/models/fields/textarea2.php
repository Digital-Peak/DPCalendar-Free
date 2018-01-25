<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2018 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */
defined('_JEXEC') or die();

JFormHelper::loadFieldClass('textarea');

class JFormFieldTextarea2 extends JFormFieldTextarea
{

	protected $type = 'Textarea2';

	public function getInput ()
	{
		$buffer = parent::getInput();
		if (isset($this->element->description))
		{
			$buffer .= '<label></label>';
			$buffer .= '<div style="float:left;">' . JText::_($this->element->description) . '</div>';
		}
		return $buffer;
	}

	public function setup (& $element, $value, $group = null)
	{
		if (isset($element->content) && empty($value))
		{
			$value = $element->content;
		}
		return parent::setup($element, $value, $group);
	}
}
