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
		$dateHelper = new \DPCalendar\Helper\DateHelper();

		$dateFormat = (string)$this->element['format'];
		if (empty($dateFormat)) {
			$dateFormat = DPCalendarHelper::getComponentParameter('event_date_format', 'm.d.Y');
		}

		$timeFormat = (string)$this->element['formatTime'];
		if (empty($timeFormat)) {
			$timeFormat = DPCalendarHelper::getComponentParameter('event_time_format', 'g:i a');
		}

		$allDay   = (string)$this->element['all_day'] == '1';
		$formated = (string)$this->element['formated'];

		// Handle the special case for "now".
		$date = null;
		if (strtoupper($this->value) == 'NOW') {
			$date = $dateHelper->getDate();
			$date->setTime($date->format('H', true), 0, 0);
		} else if (strtoupper($this->value) == '+1 HOUR' || strtoupper($this->value) == '+2 MONTH') {
			$date = $dateHelper->getDate();
			$date->setTime($date->format('H', true), 0, 0);
			$date->modify($this->value);
		} else if ($this->value && $formated) {
			$date = DPCalendarHelper::getDateFromString($this->value, null, $allDay, $dateFormat, $timeFormat);
		} else if ($this->value) {
			$date = $dateHelper->getDate($this->value, $allDay);
		}

		$layoutHelper = new \DPCalendar\Helper\LayoutHelper();
		$buffer       = $layoutHelper->renderLayout('block.datepicker', [
			'id'          => $this->id,
			'name'        => $this->name,
			'date'        => $date,
			'format'      => $dateFormat,
			'localFormat' => !$allDay,
			'firstDay'    => DPCalendarHelper::getComponentParameter('weekstart', '0'),
			'pair'        => (string)$this->element['datepair'],
			'document'    => new \DPCalendar\HTML\Document\HtmlDocument(),
			'dateHelper'  => $dateHelper
		]);

		$buffer .= $layoutHelper->renderLayout('block.timepicker', [
			'id'         => $this->id . '_time',
			'name'       => str_replace(']', '_time]', $this->name),
			'date'       => $date,
			'format'     => $timeFormat,
			'min'        => (string)$this->element['min_time'],
			'max'        => (string)$this->element['max_time'],
			'step'       => DPCalendarHelper::getComponentParameter('event_form_time_step', 30),
			'pair'       => (string)$this->element['datepair'] . '_time',
			'document'   => new \DPCalendar\HTML\Document\HtmlDocument(),
			'dateHelper' => $dateHelper
		]);

		return $buffer;
	}
}
