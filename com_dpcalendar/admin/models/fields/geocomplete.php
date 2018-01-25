<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2018 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */
defined('_JEXEC') or die();

JFormHelper::loadFieldClass('text');

class JFormFieldGeocomplete extends JFormFieldText
{

	protected $type = 'Geocomplete';

	public function getInput()
	{
		JHtml::_('script', 'com_dpcalendar/geocomplete/geocomplete.js', ['relative' => true]);

		$input = parent::getInput();

		$input .= '<button id="' . $this->id . '_find" class="btn hasTooltip" type="button" title="' . JText::_('JSEARCH_FILTER_SUBMIT') .
				 '"><i class="icon-search"></i></button>';

		return $input;
	}
}
