<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2018 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Component\DPCalendar\Administrator\Helper;

defined('_JEXEC') or die();

use DigitalPeak\Component\DPCalendar\Administrator\Translator\Translator;
use Joomla\CMS\Application\CMSWebApplicationInterface;
use Joomla\CMS\Date\Date;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Recurr\Rule;
use Recurr\Transformer\TextTransformer;
use Recurr\Transformer\Translator as TransformerTranslator;

class DateHelper
{
	private ?Translator $translator = null;

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
			$parts['UNTIL'] = substr((string)$parts['UNTIL'], 0, 8);
		}

		$translator = new TransformerTranslator();
		try {
			$translator->loadLocale(substr(DPCalendarHelper::getFrLanguage(), 0, 2));
		} catch (\InvalidArgumentException) {
			// Translation doesn't exist, ignore it
		}

		$buffer = '';
		try {
			$rule->loadFromArray($parts);
			$buffer = ucfirst((string)(new TextTransformer($translator))->transform($rule));

			if ($exdates !== null && $exdates !== [] && $this->translator instanceof Translator) {
				$buffer .= PHP_EOL;
				$buffer .= $this->translator->translate('COM_DPCALENDAR_EXDATES_EXCLUDE_LIST_TEXT');
				$buffer .= implode(', ', array_map(fn ($d): string => $this->getDate($d)->format('d.m.Y', true), $exdates));
			}
		} catch (\Exception) {
			// Do not crash as some rules are not parseable for the transformator
		}

		return $buffer;
	}

	public function getDate(Date|\DateTime|string|null $date = null, bool|int|null $allDay = null, ?string $tz = null): Date
	{
		$app     = Factory::getApplication();
		$dateObj = $date instanceof Date ? clone $date : Factory::getDate($date ?: '', $tz);

		$timezone = $app->get('offset');
		$user     = $app->getIdentity();
		if ($user && $user->id !== 0) {
			$userTimezone = $user->getParam('timezone');
			if (!empty($userTimezone)) {
				$timezone = $userTimezone;
			}
		}

		if ($app instanceof CMSWebApplicationInterface) {
			$timezone = $app->getSession()->get('DPCalendar.user-timezone', $timezone);
		}

		if ($allDay === false || $allDay === 0 || $allDay === null) {
			$dateObj->setTimezone(new \DateTimeZone($timezone));
		}

		return $dateObj;
	}

	/**
	 * @param \stdClass $event
	 */
	public function getDateStringFromEvent($event, ?string $dateFormat = null, ?string $timeFormat = null): string
	{
		return LayoutHelper::render(
			'event.datestring',
			['event' => $event, 'dateFormat' => $dateFormat, 'timeFormat' => $timeFormat],
			'',
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

	public function convertPHPDateToJS(string $format): string
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

	public function minutesToDuration(int $minutes): string
	{
		if ($minutes === 0) {
			return '00:00:00';
		}

		$buffer = str_pad((string)floor($minutes / 60), 2, '0', STR_PAD_LEFT);
		$buffer .= ':';
		$buffer .= str_pad((string)($minutes % 60), 2, (string)STR_PAD_LEFT);

		return $buffer . ':00';
	}

	public function setTranslator(Translator $translator): void
	{
		$this->translator = $translator;
	}
}
