<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2014 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */
defined('_JEXEC') or die();

JFormHelper::loadFieldClass('textarea');

class JFormFieldTextarea2 extends JFormFieldTextarea
{
	protected $type = 'Textarea2';

	public function getInput()
	{
		$buffer = parent::getInput();
		if (isset($this->element->description)) {
			$buffer .= '<label></label>';
			$buffer .= '<div style="float:left;">' . JText::_($this->element->description) . '</div>';
		}

		return $buffer;
	}

	public function setup(&$element, $value, $group = null)
	{
		if (isset($element->content) && empty($value)) {
			$value = $element->content;
		}

		return parent::setup($element, $value, $group);
	}
}
