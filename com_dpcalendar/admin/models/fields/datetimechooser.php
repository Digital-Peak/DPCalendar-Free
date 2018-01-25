<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2018 Digital Peak. All rights reserved.
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
		$options['class']      = $this->element['class'];
		$options['onchange']   = $this->element['onchange'];
		$options['allDay']     = $this->element['all_day'] == '1';
		$options['dateFormat'] = $this->element['format'];
		$options['timeFormat'] = (string)$this->element['formatTime'];
		$options['formated']   = $this->element['formated'];
		$options['timepair']   = $this->element['timepair'];
		$options['datepair']   = $this->element['datepair'];
		$options['timeclass']  = $this->element['timeclass'];
		$options['minTime']    = (string)$this->element['min_time'];
		$options['maxTime']    = (string)$this->element['max_time'];

		return JHtml::_('datetime.render', $this->value, $this->id, $this->name, $options);
	}
}
