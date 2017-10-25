<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2017 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */
defined('_JEXEC') or die();

class JFormFieldDatetimechooser extends JFormField
{

	protected $type = 'Datetimechooser';

	public function getInput()
	{
		JHtml::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_dpcalendar/helpers/html');

		$options               = array();
		$options['class']      = (string)$this->element['class'];
		$options['onchange']   = (string)$this->element['onchange'];
		$options['allDay']     = $this->element['all_day'] == '1';
		$options['dateFormat'] = (string)$this->element['format'];
		$options['timeFormat'] = (string)$this->element['formatTime'];
		$options['formated']   = (string)$this->element['formated'];
		$options['timepair']   = $this->element['timepair'];
		$options['datepair']   = (string)$this->element['datepair'];
		$options['timeclass']  = (string)$this->element['timeclass'];
		$options['minTime']    = (string)$this->element['min_time'];
		$options['maxTime']    = (string)$this->element['max_time'];

		return JHtml::_('datetime.render', $this->value, $this->id, $this->name, $options);
	}
}
