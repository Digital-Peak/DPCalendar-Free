<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2014 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

defined('_JEXEC') or die();

use DPCalendar\Helper\DateHelper;
use DPCalendar\Helper\LayoutHelper;
use DPCalendar\HTML\Document\HtmlDocument;
use Joomla\CMS\Form\FormField;
use Joomla\CMS\Language\Text;

JLoader::import('components.com_dpcalendar.helpers.dpcalendar', JPATH_ADMINISTRATOR);

class JFormFieldDatetimechooser extends FormField
{
	public $hint;
	public $element;
	public $value;
	public $id;
	public $name;
	protected $type = 'Datetimechooser';

	public function getInput()
	{
		$dateHelper = new DateHelper();

		$dateFormat = (string)$this->element['format'];
		if ($dateFormat === '' || $dateFormat === '0') {
			$dateFormat = DPCalendarHelper::getComponentParameter('event_date_format', 'd.m.Y');
		}

		$timeFormat = (string)$this->element['formatTime'];
		if ($timeFormat === '' || $timeFormat === '0') {
			$timeFormat = DPCalendarHelper::getComponentParameter('event_time_format', 'H:i');
		}

		$allDay    = (string)$this->element['all_day'] == '1';
		$formatted = (string)$this->element['formatted'];

		// Handle the special case for "now".
		$date = null;
		if (strtoupper($this->value) == 'NOW') {
			$date = $dateHelper->getDate();
			$date->setTime($date->format('H', true), 0, 0);
		} elseif (strtoupper($this->value) == '+1 HOUR' || strtoupper($this->value) == '+2 MONTH') {
			$date = $dateHelper->getDate();
			$date->setTime($date->format('H', true), 0, 0);
			$date->modify($this->value);
		} elseif ($this->value && $formatted) {
			$date = DPCalendarHelper::getDateFromString($this->value, null, $allDay, $dateFormat, $timeFormat);
		} elseif ($this->value) {
			$date = $dateHelper->getDate($this->value, $allDay);
		}

		$layoutHelper = new LayoutHelper();
		$buffer       = $layoutHelper->renderLayout('block.datepicker', [
			'id'          => $this->id,
			'name'        => $this->name,
			'date'        => $date,
			'format'      => $dateFormat,
			'localFormat' => !$allDay,
			'firstDay'    => DPCalendarHelper::getComponentParameter('weekstart', '1'),
			'pair'        => (string)$this->element['datepair'],
			'document'    => new HtmlDocument(),
			'dateHelper'  => $dateHelper,
			'title'       => $this->hint ? Text::_($this->hint) : ''
		]);

		if ((string)$this->element['show_time'] === '0') {
			return $buffer;
		}

		return $buffer . $layoutHelper->renderLayout('block.timepicker', [
			'id'         => $this->id . '_time',
			'name'       => str_replace(']', '_time]', $this->name),
			'date'       => $date,
			'format'     => $timeFormat,
			'min'        => (string)$this->element['min_time'],
			'max'        => (string)$this->element['max_time'],
			'step'       => DPCalendarHelper::getComponentParameter('event_form_time_step', 30),
			'pair'       => $this->element['datepair'] . '_time',
			'document'   => new HtmlDocument(),
			'dateHelper' => $dateHelper
		]);
	}
}
