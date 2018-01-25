<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2018 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */
defined('_JEXEC') or die();

JFormHelper::loadFieldClass('text');

class JFormFieldDptoken extends JFormFieldText
{
	protected $type = 'Dptoken';

	public function getInput()
	{
		JHtml::_('script', 'com_dpcalendar/md5/md5.min.js', ['relative' => true], ['defer' => true]);

		JFactory::getDocument()->addScriptDeclaration("
		document.addEventListener('DOMContentLoaded', function () {
			document.getElementById('" . $this->id . "-gen').onclick = function() {
				document.getElementById('" . $this->id . "').value = md5(Math.random().toString(36));
				
				return false;
			};
			
			document.getElementById('" . $this->id . "-clear').onclick = function() {
				document.getElementById('" . $this->id . "').value = '';
				
				return false;
			}
		});
		");

		$buffer = parent::getInput();

		$buffer .= '<button id="' . $this->id . '-gen" class="btn">' . htmlspecialchars(JText::_('COM_DPCALENDAR_GENERATE')) . '</button>';
		$buffer .= '<button id="' . $this->id . '-clear" class="btn">' . htmlspecialchars(JText::_('JCLEAR')) . '</button>';

		return $buffer;
	}
}
