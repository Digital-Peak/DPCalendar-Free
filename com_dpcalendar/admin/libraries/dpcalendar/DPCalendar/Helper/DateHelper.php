<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2018 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DPCalendar\Helper;

defined('_JEXEC') or die();

use DPCalendar\Translator\Translator;
use Joomla\CMS\Date\Date;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Recurr\Rule;
use Recurr\Transformer\TextTransformer;
use Recurr\Transformer\Translator as TransformerTranslator;

class DateHelper
{
	private ?\DPCalendar\Translator\Translator $translator = null;

	public function transformRRuleToString(?string $rrule, ?string $startDate, ?array $exdates = []): string
	{
		if ($rrule === null || $rrule === '' || $rrule === '0') {
			return '';
		}

		$start = $this->getDate($startDate);
		$rule  = new Rule('', $start, null, $start->getTimezone()->getName());
		$rule->setExDates($exdates !== null && $exdates !== [] ? $exdates : []);

		$parts = $rule->parseString($rrule);

		// Parser can't handle both
		if (isset($parts['UNTIL']) && isset($parts['COUNT'])) {
			unset($parts['UNTIL']);
		}

		//Only add the date so we have no tz issues
		if (isset($parts['UNTIL'])) {
			$parts['UNTIL'] = substr($parts['UNTIL'], 0, 8);
		}


		$translator = new TransformerTranslator();
		try {
			$translator->loadLocale(substr(DPCalendarHelper::getFrLanguage(), 0, 2));
		} catch (\InvalidArgumentException $invalidArgumentException) {
			// Translation doesn't exist, ignore it
		}

		$buffer = '';
		try {
			$rule->loadFromArray($parts);
			$buffer = ucfirst((new TextTransformer($translator))->transform($rule));

			if ($exdates !== null && $exdates !== []) {
				$buffer .= PHP_EOL;
				$buffer .= $this->translator->translate('COM_DPCALENDAR_EXDATES_EXCLUDE_LIST_TEXT');
				$buffer .= implode(', ', array_map(fn ($d) => $this->getDate($d)->format('d.m.Y', true), $exdates));
			}
		} catch (\Exception $exception) {
			// Do not crash as some rules are not parseable for the transformator
		}

		return $buffer;
	}

	public function getDate($date = null, $allDay = null, $tz = null)
	{
		if ($date instanceof Date) {
			$dateObj = clone $date;
		} else {
			$dateObj = Factory::getDate($date ?: '', $tz);
		}

		$timezone = Factory::getApplication()->get('offset');
		$user     = Factory::getUser();
		if ($user->id) {
			$userTimezone = $user->getParam('timezone');
			if (!empty($userTimezone)) {
				$timezone = $userTimezone;
			}
		}

		$timezone = Factory::getSession()->get('user-timezone', $timezone, 'DPCalendar');

		if (!$allDay) {
			$dateObj->setTimezone(new \DateTimeZone($timezone));
		}

		return $dateObj;
	}

	public function getDateStringFromEvent($event, $dateFormat = null, $timeFormat = null)
	{
		return LayoutHelper::render(
			'event.datestring',
			['event' => $event, 'dateFormat' => $dateFormat, 'timeFormat' => $timeFormat],
			null,
			['component' => 'com_dpcalendar', 'client' => 0]
		);
	}

	public function getNames(): array
	{
		$options                    = [];
		$options['monthNames']      = [];
		$options['monthNamesShort'] = [];
		$options['dayNames']        = [];
		$options['dayNamesShort']   = [];
		$options['dayNamesMin']     = [];
		for ($i = 0; $i < 7; $i++) {
			$options['dayNames'][]      = DPCalendarHelper::dayToString($i, false);
			$options['dayNamesShort'][] = DPCalendarHelper::dayToString($i, true);

			if (function_exists('mb_substr')) {
				$options['dayNamesMin'][] = mb_substr(DPCalendarHelper::dayToString($i, true), 0, 2);
			} else {
				$options['dayNamesMin'][] = substr(DPCalendarHelper::dayToString($i, true), 0, 2);
			}
		}
		for ($i = 1; $i <= 12; $i++) {
			$options['monthNames'][]      = DPCalendarHelper::monthToString($i, false);
			$options['monthNamesShort'][] = DPCalendarHelper::monthToString($i, true);
		}

		return $options;
	}

	public function convertPHPDateToJS($format)
	{
		$replacements = [
			'A' => 'A',      // for the sake of escaping below
			'a' => 'a',      // for the sake of escaping below
			'B' => '',       // Swatch internet time (.beats), no equivalent
			'c' => 'YYYY-MM-DD[T]HH:mm:ssZ', // ISO 8601
			'D' => 'ddd',
			'd' => 'DD',
			'e' => 'zz',     // deprecated since version 1.6.0 of dayjs.js
			'F' => 'MMMM',
			'G' => 'H',
			'g' => 'h',
			'H' => 'HH',
			'h' => 'hh',
			'I' => '',       // Daylight Saving Time? => dayjs().isDST();
			'i' => 'mm',
			'j' => 'D',
			'L' => '',       // Leap year? => dayjs().isLeapYear();
			'l' => 'dddd',
			'M' => 'MMM',
			'm' => 'MM',
			'N' => 'E',
			'n' => 'M',
			'O' => 'ZZ',
			'o' => 'YYYY',
			'P' => 'Z',
			'r' => 'ddd, DD MMM YYYY HH:mm:ss ZZ', // RFC 2822
			'S' => 'o',
			's' => 'ss',
			'T' => 'z',      // deprecated since version 1.6.0 of dayjs.js
			't' => '',       // days in the month => dayjs().daysInMonth();
			'U' => 'X',
			'u' => 'SSSSSS', // microseconds
			'v' => 'SSS',    // milliseconds (from PHP 7.0.0)
			'W' => 'W',      // for the sake of escaping below
			'w' => 'e',
			'Y' => 'YYYY',
			'y' => 'YY',
			'Z' => '',       // time zone offset in minutes => dayjs().zone();
			'z' => 'DDD',
		];

		// Converts escaped characters
		foreach (array_keys($replacements) as $from) {
			$replacements['\\' . $from] = '[' . $from . ']';
		}

		return Text::_(strtr($format, $replacements));
	}

	public function minutesToDuration($minutes): string
	{
		if (!$minutes) {
			return '00:00:00';
		}

		$buffer = str_pad(floor($minutes / 60), 2, '0', STR_PAD_LEFT);
		$buffer .= ':';
		$buffer .= str_pad($minutes % 60, 2, STR_PAD_LEFT);

		return $buffer . ':00';
	}

	public function setTranslator(Translator $translator): void
	{
		$this->translator = $translator;
	}
}
