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

JFormHelper::loadFieldClass('text');

class JFormFieldDptoken extends JFormFieldText
{
	protected $type = 'Dptoken';

	public function getInput()
	{
		(new \DPCalendar\HTML\Document\HtmlDocument())->loadScriptFile('dpcalendar/fields/dptoken.js');

		// Load the language
		JFactory::getLanguage()->load('com_dpcalendar', JPATH_ADMINISTRATOR . '/components/com_dpcalendar');

		$buffer = parent::getInput();

		$buffer .= '<button class="btn dp-token-gen">' . htmlspecialchars(JText::_('COM_DPCALENDAR_GENERATE')) . '</button>';
		$buffer .= '<button class="btn dp-token-clear">' . htmlspecialchars(JText::_('JCLEAR')) . '</button>';

		return $buffer;
	}
}
