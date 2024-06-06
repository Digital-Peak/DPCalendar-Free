<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2017 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Component\DPCalendar\Administrator\Helper;

defined('_JEXEC') or die();

use DigitalPeak\Component\DPCalendar\Administrator\Calendar\CalendarInterface;
use DigitalPeak\Component\DPCalendar\Site\Helper\RouteHelper;
use Joomla\CMS\Access\Access;
use Joomla\CMS\Application\CMSWebApplicationInterface;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Date\Date;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\FormField;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Language;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Mail\Exception\MailDisabledException;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Response\JsonResponse;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Joomla\Registry\Registry;

class DPCalendarHelper
{
	public static array $DISABLED_FREE_FIELDS = [
		'rrule',
		'exdates',
		'capacity_used',
		'max_tickets',
		'booking_closing_date',
		'booking_cancel_closing_date',
		'booking_series',
		'booking_waiting_list',
		'price',
		'earlybird',
		'user_discount',
		'booking_options',
		'payment_provider',
		'terms',
		'booking_assign_user_groups',
		'booking_information'
	];

	public static function getCalendar(mixed $id): ?CalendarInterface
	{
		return Factory::getApplication()->bootComponent('dpcalendar')->getMVCFactory()->createModel('Calendar', 'Administrator')->getCalendar($id);
	}

	public static function getComponentParameter(string $key, mixed $defaultValue = null): mixed
	{
		$params = ComponentHelper::getParams('com_dpcalendar');

		return $params->get($key, $defaultValue);
	}

	public static function getFrLanguage(): string
	{
		$language = Factory::getApplication()->get('language', 'en-GB');

		// @phpstan-ignore-next-line
		$user = Factory::getUser();
		if ($user->id) {
			$userLanguage = $user->getParam('language', 'en-GB');
			if (!empty($userLanguage)) {
				$language = $userLanguage;
			}
		}

		return $language;
	}

	/**
	 * Obfuscates the given string.
	 */
	public static function obfuscate(string $string): string
	{
		return base64_encode(@convert_uuencode($string));
	}

	/**
	 * Deobfuscates the given string.
	 */
	public static function deobfuscate(string $string): string
	{
		try {
			$tmp = @convert_uudecode(base64_decode($string));

			// Probably not obfuscated
			if ($tmp === '' || $tmp === '0' || $tmp === false) {
				return $string;
			}

			return $tmp;
		} catch (\Exception) {
			return $string;
		}
	}

	public static function parseReadMore(\stdClass $event): void
	{
		if (!empty($event->introText)) {
			return;
		}

		$pattern = '#<hr\s+id=("|\')system-readmore("|\')\s*\/*>#i';

		$match = preg_match($pattern, (string)$event->description);
		if (!$event->description || ($match === 0 || $match === false)) {
			$event->introText = '';
			return;
		}

		[$event->introText, $event->description] = preg_split($pattern, (string)$event->description, 2) ?: ['', ''];
	}

	/**
	 * @param \stdClass $event
	 */
	public static function parseImages($event): void
	{
		if (is_string($event->images)) {
			$event->images = json_decode($event->images);
		}

		$images        = $event->images;
		$event->images = new \stdClass();


		$event->images->image_intro         = $images->image_intro ?? null;
		$event->images->image_intro_width   = $images->image_intro_width ?? null;
		$event->images->image_intro_height  = $images->image_intro_height ?? null;
		$event->images->image_intro_alt     = $images->image_intro_alt ?? null;
		$event->images->image_intro_caption = $images->image_intro_caption ?? null;
		$dimensions                         = $event->images->image_intro_width ? ' width="' . $event->images->image_intro_width . '"' : '';
		$dimensions .= $event->images->image_intro_height ? ' height="' . $event->images->image_intro_height . '"' : '';
		$event->images->image_intro_dimensions = trim($dimensions);

		$event->images->image_full         = $images->image_full ?? null;
		$event->images->image_full_width   = $images->image_full_width ?? null;
		$event->images->image_full_height  = $images->image_full_height ?? null;
		$event->images->image_full_alt     = $images->image_full_alt ?? null;
		$event->images->image_full_caption = $images->image_full_caption ?? null;
		$dimensions                        = $event->images->image_full_width ? ' width="' . $event->images->image_full_width . '"' : '';
		$dimensions .= $event->images->image_full_height ? ' height="' . $event->images->image_full_height . '"' : '';
		$event->images->image_full_dimensions = trim($dimensions);

		if (!empty($event->images->image_intro) && $pos = strpos((string)$event->images->image_intro, '#')) {
			$event->images->image_intro = substr((string)$event->images->image_intro, 0, $pos);
		}
		if (empty($event->images->image_full)) {
			return;
		}
		if (($pos = strpos((string)$event->images->image_full, '#')) === 0 || ($pos = strpos((string)$event->images->image_full, '#')) === false) {
			return;
		}
		$event->images->image_full = substr((string)$event->images->image_full, 0, $pos);
	}

	public static function dayToString(int $day, bool $abbr = false): string
	{
		$date = new Date();

		return addslashes($date->dayToString($day, $abbr));
	}

	public static function monthToString(int $month, bool $abbr = false): string
	{
		$date = new Date();

		return addslashes($date->monthToString($month, $abbr));
	}

	public static function getDate(mixed $date = null, ?bool $allDay = false, ?string $tz = null): Date
	{
		$dateObj = $date instanceof Date ? clone $date : Factory::getDate($date ?? '', $tz);
		if ($allDay === true) {
			return $dateObj;
		}

		$app = Factory::getApplication();

		$timezone = $app->get('offset');
		$user     = $app->getIdentity();
		if ($user && $user->id !== 0) {
			$userTimezone = $user->getParam('timezone');
			if (!empty($userTimezone)) {
				$timezone = $userTimezone;
			}
		}

		if ($timezone && $app instanceof CMSWebApplicationInterface) {
			$dateObj->setTimezone(new \DateTimeZone($app->getSession()->get('DPCalendar.user-timezone', $timezone)));
		}

		return $dateObj;
	}

	public static function getDateFromString(mixed $date, ?string $time, bool $allDay, string $dateFormat = null, string $timeFormat = null): Date
	{
		$string = $date;
		if ($time !== null && $time !== '' && $time !== '0') {
			$string = $date . ($allDay ? '' : ' ' . $time);
		}

		$replaces = [
			'JANUARY',
			'FEBRUARY',
			'MARCH',
			'APRIL',
			'MAY',
			'JUNE',
			'JULY',
			'AUGUST',
			'SEPTEMBER',
			'OCTOBER',
			'NOVEMBER',
			'DECEMBER',
			'JANUARY_SHORT',
			'FEBRUARY_SHORT',
			'MARCH_SHORT',
			'APRIL_SHORT',
			'MAY_SHORT',
			'JUNE_SHORT',
			'JULY_SHORT',
			'AUGUST_SHORT',
			'SEPTEMBER_SHORT',
			'OCTOBER_SHORT',
			'NOVEMBER_SHORT',
			'DECEMBER_SHORT',
			'SATURDAY',
			'SUNDAY',
			'MONDAY',
			'TUESDAY',
			'WEDNESDAY',
			'THURSDAY',
			'FRIDAY',
			'SAT',
			'SUN',
			'MON',
			'TUE',
			'WED',
			'THU',
			'FRI'
		];

		// @phpstan-ignore-next-line
		$lang = Language::getInstance('en-GB');
		foreach ($replaces as $key) {
			$string = str_replace(Text::_($key), $lang->_($key), (string)$string);
		}

		if ($dateFormat === null || $dateFormat === '' || $dateFormat === '0') {
			$dateFormat = self::getComponentParameter('event_form_date_format', 'd.m.Y');
		}
		if ($timeFormat === null || $timeFormat === '' || $timeFormat === '0') {
			$timeFormat = self::getComponentParameter('event_form_time_format', 'H:i');
		}

		$date = self::getDate(null, $allDay);
		$date = \DateTime::createFromFormat($dateFormat . ($allDay ? '' : ' ' . $timeFormat), $string, $date->getTimezone());
		if ($date == null) {
			$errors = \DateTime::getLastErrors();
			if ($errors) {
				throw new \Exception('Could not interprete format: ' . ($dateFormat . ($allDay ? '' : ' ' . $timeFormat)) .
					' for date string : ' . $string . PHP_EOL .
					'Error was: ' . implode(',', $errors['warnings']) . ' ' . implode(',', $errors['errors']));
			}

			throw new \Exception('Could not interprete format: ' . ($dateFormat . ($allDay ? '' : ' ' . $timeFormat)) . ' for date string : ' . $string);
		}

		return self::getDate($date->format('U'), $allDay);
	}

	/**
	 * @param \stdClass $event
	 */
	public static function getDateStringFromEvent($event, string $dateFormat = null, string $timeFormat = null, bool $noTags = false): string
	{
		$text = LayoutHelper::render(
			'event.datestring',
			['event' => $event, 'dateFormat' => $dateFormat, 'timeFormat' => $timeFormat],
			'',
			['component' => 'com_dpcalendar', 'client' => 0]
		);

		if (!$noTags) {
			return $text;
		}

		$text = strip_tags($text);
		$text = preg_replace("/\s\s+/", ' ', $text);

		return trim((string)$text);
	}

	public static function dateStringToDatepickerFormat(string $dateString): string
	{
		$pattern = [
			'd',
			'j',
			'l',
			'z',
			'F',
			'M',
			'n',
			'm',
			'Y',
			'y'
		];
		$replace = [
			'dd',
			'd',
			'DD',
			'o',
			'MM',
			'M',
			'm',
			'mm',
			'yy',
			'y'
		];
		foreach ($pattern as &$p) {
			$p = '/' . $p . '/';
		}

		// @phpstan-ignore-next-line
		return preg_replace($pattern, $replace, $dateString) !== '' && preg_replace($pattern, $replace, $dateString) !== '0' && preg_replace($pattern, $replace, $dateString) !== [] ? preg_replace($pattern, $replace, $dateString) : '';
	}

	public static function renderEvents(array $events = null, string $output = '', ?Registry $params = null, array $eventParams = []): string
	{
		if ($events === null) {
			$events = [];
		}

		if ($params == null) {
			$params = ComponentHelper::getParams('com_dpcalendar');
		}

		Factory::getApplication()->getLanguage()->load('com_dpcalendar', JPATH_ADMINISTRATOR . '/components/com_dpcalendar');

		$return = Factory::getApplication()->getInput()->getInt('Itemid', 0);
		$return = empty($return) ? '' : Route::_('index.php?Itemid=' . $return, false);

		// @phpstan-ignore-next-line
		$user = Factory::getUser();

		$lastHeading = '';

		$configuration           = $eventParams;
		$configuration['events'] = [];
		$locationCache           = [];
		foreach ($events as $event) {
			$variables = [];

			$calendar = Factory::getApplication()->bootComponent('dpcalendar')->getMVCFactory()->createModel('Calendar', 'Administrator')->getCalendar($event->catid);

			$variables['canEdit']    = $calendar instanceof CalendarInterface && ($calendar->canEdit() || ($calendar->canEditOwn() && $event->created_by == $user->id));
			$variables['editLink']   = RouteHelper::getFormRoute($event->id, $return);
			$variables['canDelete']  = $calendar instanceof CalendarInterface && ($calendar->canDelete() || ($calendar->canEditOwn() && $event->created_by == $user->id));
			$variables['deleteLink'] = Route::link(
				'site',
				'index.php?option=com_dpcalendar&task=event.delete&e_id=' . $event->id . ($return ? '&return=' . base64_encode($return) : '')
			);

			$variables['canBook']  = Booking::openForBooking($event);
			$variables['bookLink'] = RouteHelper::getBookingFormRouteFromEvent($event, $return);
			$variables['booking']  = isset($event->booking) && (bool)$event->booking;

			$variables['calendarLink'] = RouteHelper::getCalendarRoute($event->catid ?: 0);
			$variables['backLink']     = RouteHelper::getEventRoute($event->id, $event->catid);
			$variables['backLinkFull'] = RouteHelper::getEventRoute($event->id, $event->catid, true);

			// The date formats from http://php.net/date
			$dateformat = $params->get('event_date_format', 'd.m.Y');
			$timeformat = $params->get('event_time_format', 'H:i');

			// These are the dates we'll display
			$startDate     = self::getDate($event->start_date, $event->all_day)->format($dateformat, true);
			$startTime     = self::getDate($event->start_date, $event->all_day)->format($timeformat, true);
			$endDate       = self::getDate($event->end_date, $event->all_day)->format($dateformat, true);
			$endTime       = self::getDate($event->end_date, $event->all_day)->format($timeformat, true);
			$dateSeparator = '-';

			$copyDateTimeFormat = 'Ymd';
			if ($event->all_day) {
				$startTime = '';
				$endTime   = '';
			} else {
				$copyDateTimeFormat = 'Ymd\THis';
			}
			if ($startDate === $endDate) {
				$endDate       = '';
				$dateSeparator = '';
			}

			$variables['color'] = $event->color;
			if (empty($variables['color']) && $calendar != null) {
				$variables['color'] = $calendar->getColor();
			}

			$variables['id']            = $event->id;
			$variables['calendarName']  = $calendar != null ? $calendar->getTitle() : $event->catid;
			$variables['title']         = $event->title;
			$variables['date']          = strip_tags(self::getDateStringFromEvent($event, $dateformat, $timeformat));
			$variables['d']             = $variables['date'];
			$variables['startDate']     = $startDate;
			$variables['startDateIso']  = self::getDate($event->start_date, $event->all_day)->format('c');
			$variables['startTime']     = $startTime;
			$variables['endDate']       = $endDate;
			$variables['endDateIso']    = self::getDate($event->end_date, $event->all_day)->format('c');
			$variables['endTime']       = $endTime;
			$variables['dateSeparator'] = $dateSeparator;

			$variables['monthNr'] = self::getDate($event->start_date, $event->all_day)->format('m', true);
			$variables['year']    = self::getDate($event->start_date, $event->all_day)->format('Y', true);
			$variables['month']   = self::getDate($event->start_date, $event->all_day)->format('M', true);
			$variables['day']     = self::getDate($event->start_date, $event->all_day)->format('j', true);

			if (isset($event->tickets) && !empty($event->tickets)) {
				$variables['ticket'] = $event->tickets;
			}

			$location = '';
			if (isset($event->locations) && !empty($event->locations)) {
				$variables['location'] = $event->locations;
				foreach ($event->locations as $location) {
					if (array_key_exists($location->id, $locationCache)) {
						$location = $locationCache[$location->id];
					} else {
						$tmp                          = Factory::getApplication()->bootComponent('dpcalendar')->getMVCFactory()->createModel('Geo', 'Administrator')->format($location);
						$location->full               = $tmp;
						$locationCache[$location->id] = $tmp;
						$location                     = $tmp;
					}
				}
			}

			$variables['description_truncated'] = empty($event->truncatedDescription) ? '' : $event->truncatedDescription;

			try {
				$variables['description'] = HTMLHelper::_('content.prepare', $event->description ?: '', $params, 'com_dpcalendar.event');
			} catch (\Exception) {
				$variables['description'] = $event->description;
			}
			if ($params->get('description_length', 0) > 0) {
				$variables['description'] = HTMLHelper::_('string.truncate', $variables['description'], $params->get('description_length', 0));
			}

			$variables['url']   = $event->url;
			$variables['hits']  = $event->hits;
			$variables['state'] = $event->state;

			$status = 'JUNPUBLISHED';
			switch ($event->state) {
				case 0:
					$status = 'JUNPUBLISHED';
					break;
				case 1:
					$status = 'JPUBLISHED';
					break;
				case 2:
					$status = 'JARCHIVED';
					break;
				case 3:
					$status = 'COM_DPCALENDAR_FIELD_VALUE_CANCELED';
					break;
				case -2:
					$status = 'JTRASHED';
					break;
				case -3:
					$status = 'COM_DPCALENDAR_FIELD_VALUE_REPORTED';
					break;
			}
			$variables['stateName'] = Text::_($status);

			self::parseImages($event);
			$variables['images'] = $event->images;

			// @phpstan-ignore-next-line
			$author                  = Factory::getUser($event->created_by);
			$variables['author']     = $author->name;
			$variables['authorMail'] = $author->email;
			if (!empty($event->created_by_alias)) {
				$variables['author'] = $event->created_by_alias;
			}
			$variables['avatar'] = self::getAvatar($author->id, $author->email ?: '', $params);

			$variables['capacity']          = $event->capacity == null ? Text::_('COM_DPCALENDAR_FIELD_CAPACITY_UNLIMITED') : $event->capacity;
			$variables['capacityUsed']      = $event->capacity_used;
			$variables['capacityRemaining'] = $event->capacity == null ? null : $event->capacity - $event->capacity_used;
			if (isset($event->bookings)) {
				foreach ($event->bookings as $booking) {
					if ($booking->user_id < 1) {
						continue;
					}
					$booking->avatar = self::getAvatar($booking->id, $booking->email, $params);
				}
				$variables['bookings'] = $event->bookings;
			}

			$end = self::getDate($event->end_date, $event->all_day);
			if ($event->all_day) {
				$end->modify('+1 day');
			}
			$variables['copyGoogleUrl'] = 'http://www.google.com/calendar/render?action=TEMPLATE&text=' . urlencode((string)$event->title);
			$variables['copyGoogleUrl'] .= '&dates=' . self::getDate($event->start_date, $event->all_day)->format($copyDateTimeFormat, true) . '%2F' .
				$end->format($copyDateTimeFormat, true);
			$variables['copyGoogleUrl'] .= '&location=' . urlencode((string)$location);

			if ($event->description) {
				$variables['copyGoogleUrl'] .= '&details=' . urlencode((string)HTMLHelper::_('string.truncate', $event->description, 200));
			}

			$variables['copyGoogleUrl'] .= '&hl=' . self::getFrLanguage() . '&ctz=' .
				self::getDate($event->start_date, $event->all_day)->getTimezone()->getName();
			$variables['copyGoogleUrl'] .= '&sf=true&output=xml';

			$variables['copyOutlookUrl'] = Route::link('site', 'index.php?option=com_dpcalendar&view=event&format=raw&id=' . $event->id);

			$groupHeading = self::getDate($event->start_date, $event->all_day)->format($params->get('grouping', ''), true);
			if ($groupHeading !== $lastHeading) {
				$lastHeading         = $groupHeading;
				$variables['header'] = $groupHeading;
			}

			if (!empty($event->jcfields)) {
				foreach ($event->jcfields as $field) {
					$variables['field-' . $field->name] = $field;
				}
			}

			$configuration['events'][] = $variables;
		}

		$configuration['canCreate']  = self::canCreateEvent();
		$configuration['createLink'] = RouteHelper::getFormRoute('0', $return);

		$configuration['calendarNameLabel'] = Text::_('COM_DPCALENDAR_CALENDAR');
		$configuration['titleLabel']        = Text::_('COM_DPCALENDAR_FIELD_CONFIG_EVENT_LABEL_TITLE');
		$configuration['dateLabel']         = Text::_('COM_DPCALENDAR_DATE');
		$configuration['locationLabel']     = Text::_('COM_DPCALENDAR_LOCATION');
		$configuration['descriptionLabel']  = Text::_('COM_DPCALENDAR_DESCRIPTION');
		$configuration['commentsLabel']     = Text::_('COM_DPCALENDAR_FIELD_CONFIG_EVENT_LABEL_COMMENTS');
		$configuration['eventLabel']        = Text::_('COM_DPCALENDAR_EVENT');
		$configuration['authorLabel']       = Text::_('COM_DPCALENDAR_FIELD_CONFIG_EVENT_LABEL_AUTHOR');
		$configuration['bookingsLabel']     = Text::_('COM_DPCALENDAR_FIELD_CONFIG_EVENT_LABEL_BOOKINGS');
		$configuration['bookLabel']         = Text::_('COM_DPCALENDAR_BOOK');
		$configuration['bookingLabel']      = Text::_('COM_DPCALENDAR_BOOKED');
		$configuration['capacityLabel']     = Text::_('COM_DPCALENDAR_FIELD_CAPACITY_LABEL');
		$configuration['capacityUsedLabel'] = Text::_('COM_DPCALENDAR_FIELD_CAPACITY_USED_LABEL');
		$configuration['hitsLabel']         = Text::_('COM_DPCALENDAR_FIELD_CONFIG_EVENT_LABEL_HITS');
		$configuration['urlLabel']          = Text::_('COM_DPCALENDAR_FIELD_CONFIG_EVENT_LABEL_URL');
		$configuration['copyLabel']         = Text::_('COM_DPCALENDAR_FIELD_CONFIG_EVENT_LABEL_COPY');
		$configuration['copyGoogleLabel']   = Text::_('COM_DPCALENDAR_FIELD_CONFIG_EVENT_LABEL_COPY_GOOGLE');
		$configuration['copyOutlookLabel']  = Text::_('COM_DPCALENDAR_FIELD_CONFIG_EVENT_LABEL_COPY_OUTLOOK');
		$configuration['language']          = substr(self::getFrLanguage(), 0, 2);
		$configuration['editLabel']         = Text::_('JACTION_EDIT');
		$configuration['createLabel']       = Text::_('JACTION_CREATE');
		$configuration['deleteLabel']       = Text::_('JACTION_DELETE');

		$configuration['emptyText'] = Text::_('COM_DPCALENDAR_FIELD_CONFIG_EVENT_LABEL_NO_EVENT_TEXT');

		try {
			$m = new \Mustache_Engine();

			return $m->render($output, $configuration);
		} catch (\Exception $exception) {
			echo $exception->getMessage();
		}

		return '';
	}

	public static function renderPrice(string $price = '', string $currencySymbol = null, string $separator = null, string $thousandSeparator = null): string
	{
		if ($price === '') {
			return $price;
		}

		return self::renderLayout(
			'format.price',
			['price' => $price, 'currency' => $currencySymbol, 'separator' => $separator, 'thousands_separator' => $thousandSeparator]
		);
	}

	public static function renderLayout(string $layout, array $data = []): string
	{
		// Framework specific content is not loaded
		return LayoutHelper::render($layout, $data, '', ['component' => 'com_dpcalendar', 'client' => 0]);
	}

	public static function getStringFromParams(string $key, mixed $default, Registry $params, ?Language $language = null): string
	{
		$text = $params->get($key, $default);
		$text = trim(strip_tags((string)$text));

		if (!$language instanceof Language) {
			$language = Factory::getApplication()->getLanguage();
		}

		if ($language->hasKey($text)) {
			return $language->_($text);
		}

		return $params->get($key, $default);
	}

	public static function getAvatar(int $userId, string $email, Registry $params): string
	{
		$image          = null;
		$avatarProvider = $params->get('avatar', 1);
		if ($avatarProvider == 2) {
			$size = $params->get('avatar_width', 0);
			if ($size == 0) {
				$size = $params->get('avatar_height', 0);
			}
			$size  = $size == 0 ? '' : '?s=' . $size;
			$image = 'https://www.gravatar.com/avatar/' . md5(strtolower(trim($email))) . $size;
		}

		$cbLoader  = JPATH_ADMINISTRATOR . '/components/com_comprofiler/plugin.foundation.php';
		$jomsocial = JPATH_ROOT . '/components/com_community/libraries/core.php';
		$easy      = JPATH_ADMINISTRATOR . '/components/com_easysocial/includes/foundry.php';
		if ((($avatarProvider == 1 && !file_exists($jomsocial)) || $avatarProvider == 4) && file_exists($cbLoader)) {
			include_once $cbLoader;
			// @phpstan-ignore-next-line
			$cbUser = \CBuser::getInstance($userId);
			if ($cbUser !== null) {
				$image = $cbUser->getField('avatar', null, 'csv');
			}
			if (empty($image)) {
				// @phpstan-ignore-next-line
				$image = selectTemplate() . 'images/avatar/tnnophoto_n.png';
			}
		}
		if (($avatarProvider == 1 || $avatarProvider == 3) && file_exists($jomsocial)) {
			include_once $jomsocial;
			$image = \CFactory::getUser($userId)->getThumbAvatar();
		}
		if (($avatarProvider == 1 || $avatarProvider == 5) && file_exists($easy)) {
			// @phpstan-ignore-next-line
			$image = \Foundry::user($userId)->getAvatar();
		}
		if ($image != null) {
			$w = $params->get('avatar_width', 0);
			$h = $params->get('avatar_height', 0);
			if ($w != 0) {
				$w = 'width="' . $w . '"';
			} elseif ($h == 0) {
				$w = 'width="80"';
			} else {
				$w = '';
			}
			$h = $h != 0 ? 'height="' . $h . '"' : '';

			return '<img src="' . $image . '" ' . $w . ' ' . $h . ' loading="lazy"/>';
		}

		return '';
	}

	public static function getGoogleLanguage(): string
	{
		$languages = [
			'ar',
			'bg',
			'bn',
			'ca',
			'cs',
			'da',
			'de',
			'el',
			'en',
			'en-AU',
			'en-GB',
			'es',
			'eu',
			'fa',
			'fi',
			'fil',
			'fr',
			'gl',
			'gu',
			'hi',
			'hr',
			'hu',
			'id',
			'it',
			'iw',
			'ja',
			'kn',
			'ko',
			'lt',
			'lv',
			'ml',
			'mr',
			'nl',
			'nn',
			'no',
			'or',
			'pl',
			'pt',
			'pt-BR',
			'pt-PT',
			'rm',
			'ro',
			'ru',
			'sk',
			'sl',
			'sr',
			'sv',
			'tl',
			'ta',
			'te',
			'th',
			'tr',
			'uk',
			'vi',
			'zh-CN',
			'zh-TW'
		];
		$lang = self::getFrLanguage();
		if (!in_array($lang, $languages)) {
			$lang = substr($lang, 0, strpos($lang, '-') ?: 0);
		}
		if (!in_array($lang, $languages)) {
			return 'en';
		}

		return $lang;
	}

	public static function canCreateEvent(): bool
	{
		// @phpstan-ignore-next-line
		$user   = Factory::getUser();
		$canAdd = $user->authorise('core.create', 'com_dpcalendar') || count($user->getAuthorisedCategories('com_dpcalendar', 'core.create'));

		if (!$canAdd) {
			PluginHelper::importPlugin('dpcalendar');
			$tmp = Factory::getApplication()->triggerEvent('onCalendarsFetch');
			if (!empty($tmp)) {
				foreach ($tmp as $tmpCalendars) {
					foreach ($tmpCalendars as $calendar) {
						if ($calendar->canCreate()) {
							$canAdd = true;
							break;
						}
					}
				}
			}
		}

		return $canAdd;
	}

	public static function isFree(): bool
	{
		return !file_exists(JPATH_ADMINISTRATOR . '/components/com_dpcalendar/src/Table/BookingTable.php');
	}

	public static function isCaptchaNeeded(): bool
	{
		PluginHelper::importPlugin('captcha');

		// @phpstan-ignore-next-line
		$userGroups    = Access::getGroupsByUser(Factory::getUser()->id, false);
		$accessCaptcha = array_intersect(self::getComponentParameter('captcha_groups', [1]), $userGroups);

		return PluginHelper::isEnabled('captcha') && $accessCaptcha;
	}

	public static function sendMessage(?string $message = '', bool $error = false, array $data = []): void
	{
		ob_clean();

		header('Content-Type: application/json');

		if ($message !== '' && $message !== '0' && $message !== null) {
			Factory::getApplication()->enqueueMessage($message, $error ? 'error' : 'message');
		}
		echo new JsonResponse($data);

		Factory::getApplication()->close();
	}

	/**
	 * Sends a mail to all users of the given component parameter groups.
	 * The user objects are returned where a mail is sent to.
	 */
	public static function sendMail(string $subject, string $message, string $parameter, array $additionalGroups = [], string $fromMail = null): array
	{
		$groups = self::getComponentParameter($parameter);

		if (!is_array($groups)) {
			$groups = [$groups];
		}

		$groups = array_unique(array_filter(array_merge($groups, $additionalGroups)));
		if ($groups === []) {
			return [];
		}

		$users = [];
		foreach ($groups as $groupId) {
			$users = array_merge($users, Access::getUsersByGroup($groupId));
		}

		// @phpstan-ignore-next-line
		$currentUser = Factory::getUser();
		$users       = array_unique($users);
		$userMails   = [];
		foreach ($users as $userId) {
			// @phpstan-ignore-next-line
			$user = Factory::getUser($userId);
			if ($user->id == $currentUser->id) {
				continue;
			}

			// @phpstan-ignore-next-line
			$mailer = Factory::getMailer();

			if ($fromMail !== null && $fromMail !== '' && $fromMail !== '0') {
				$mailer->setFrom($fromMail);
			}

			$mailer->setSubject($subject);
			$mailer->setBody($message);
			$mailer->IsHTML(true);
			$mailer->addRecipient($user->email);

			try {
				$mailer->Send();
				$userMails[$userId] = $user;
			} catch (MailDisabledException) {
			}
		}

		return $userMails;
	}

	/**
	 * Checks if the haystack starts with the needle.
	 *
	 * @param string $haystack
	 * @param string $needle
	 *
	 * @return boolean
	 */
	public static function startsWith($haystack, $needle): bool
	{
		return (str_starts_with($haystack, $needle));
	}

	/**
	 * Checks if the haystack ends with the needle.
	 *
	 * @param string $haystack
	 * @param string $needle
	 *
	 * @return boolean
	 */
	public static function endsWith($haystack, $needle): bool
	{
		$length = strlen($needle);
		if ($length == 0) {
			return true;
		}

		return substr($haystack, -$length) === $needle;
	}

	public static function matches(string $text, string $query): bool
	{
		$query = str_replace('+', '', $query);
		$tmp   = str_getcsv($query, ' ');

		// str_getcsv creates from '-"foo bar"' > ['-"foo', 'bar"'] it needs to
		// be combined back
		$criteria  = [];
		$appending = null;
		foreach ($tmp as $key => $value) {
			if (self::startsWith($value !== null && $value !== '' && $value !== '0' ? $value : '', '-"')) {
				$criteria[$key] = str_replace('-"', '-', (string)$value);
				$appending      = $key;
				continue;
			}

			if ($appending !== null && $appending !== 0) {
				$criteria[$appending] .= ' ' . str_replace('"', '', (string)$value);
				if (self::endsWith($value !== null && $value !== '' && $value !== '0' ? $value : '', '"')) {
					$appending = null;
				}
				continue;
			}

			$criteria[$key] = $value;
		}

		$criteria = array_values($criteria);

		foreach ($criteria as $q) {
			if (empty($q)) {
				continue;
			}
			if (self::startsWith($q, '-')) {
				if (str_contains($text, substr($q, 1))) {
					return false;
				}
			} elseif (self::startsWith($q, '+')) {
				if (!str_contains($text, substr($q, 1))) {
					return false;
				}
			} elseif (!str_contains($text, $q)) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Sort the given fields array based on the given order. The order is a setting from a subform field.
	 */
	public static function sortFields(array &$fields, \stdClass|array $order): void
	{
		$order = (array)$order;

		// Move captcha to bottom
		if (!in_array('captcha', $order)) {
			foreach ($fields as $index => $field) {
				if (!$field instanceof FormField || $field->fieldname != 'captcha') {
					continue;
				}

				unset($fields[$index]);
				$fields[$index] = $field;
				break;
			}
		}

		// Check if empty
		if ($order === []) {
			return;
		}

		// Get the field names out of the object
		$order = array_values(array_map(static fn ($f) => $f->field ?? $f['field'], $order));

		// get the keys
		$keys = array_keys($fields);

		// Sort the fields
		usort(
			$fields,
			static function ($f1, $f2) use ($order, $keys) {
				$fieldName = property_exists($f1, 'fieldname') ? 'fieldname' : 'name';
				$k1        = in_array($f1->{$fieldName}, $order) ? array_search($f1->{$fieldName}, $order, true) : -1;
				$k2        = in_array($f2->{$fieldName}, $order) ? array_search($f2->{$fieldName}, $order, true) : -1;

				if ($k1 >= 0 && $k2 < 0) {
					return -1;
				}

				if ($k1 < 0 && $k2 >= 0) {
					return 1;
				}

				// @phpstan-ignore-next-line
				if ($k1 >= 0 && $k2 >= 0) {
					return $k1 > $k2 ? 1 : -1;
				}

				return array_search($f1->id, $keys, true) - array_search($f2->id, $keys, true);
			}
		);
	}

	public static function getFieldName(FormField $field, bool $forRender = false): string
	{
		$fieldName = str_replace($field->formControl . '_', '', (string)$field->id);
		if ($field->group) {
			$fieldName = str_replace($field->group . '_', '', $fieldName);
		}

		if ($forRender) {
			return 'dp-field-' . str_replace('_', '-', $fieldName);
		}

		return $fieldName;
	}

	public static function parseHtml(string $text): string
	{
		$text = str_replace('\n', PHP_EOL, $text);

		// IE does not handle &apos; entity!
		$text = preg_replace('/&apos;/', '&#39;', $text);

		$section_html_pattern = '%# Rev:20100913_0900 github.com/jmrware/LinkifyURL
		# Section text into HTML <A> tags  and everything else.
		(                              # $1: Everything not HTML <A> tag.
		[^<]+(?:(?!<a\b)<[^<]*)*     # non A tag stuff starting with non-"<".
		|      (?:(?!<a\b)<[^<]*)+     # non A tag stuff starting with "<".
		)                              # End $1.
		| (                              # $2: HTML <A...>...</A> tag.
		<a\b[^>]*>                   # <A...> opening tag.
		[^<]*(?:(?!</a\b)<[^<]*)*    # A tag contents.
		</a\s*>                      # </A> closing tag.
		)                              # End $2:
		%ix';
		$text = preg_replace_callback($section_html_pattern, static fn (array $matches): string => DPCalendarHelper::linkifyHtmlCallback($matches), (string)$text);

		return nl2br((string)$text);
	}

	public static function linkify(string $text): string
	{
		$url_pattern = '/# Rev:20100913_0900 github.com\/jmrware\/LinkifyURL
		# Match http & ftp URL that is not already linkified.
		# Alternative 1: URL delimited by (parentheses).
		(\()                     # $1  "(" start delimiter.
		((?:ht|f)tps?:\/\/[a-z0-9\-._~!$&\'()*+,;=:\/?#[\]@%]+)  # $2: URL.
		(\))                     # $3: ")" end delimiter.
		| # Alternative 2: URL delimited by [square brackets].
		(\[)                     # $4: "[" start delimiter.
		((?:ht|f)tps?:\/\/[a-z0-9\-._~!$&\'()*+,;=:\/?#[\]@%]+)  # $5: URL.
		(\])                     # $6: "]" end delimiter.
		| # Alternative 3: URL delimited by {curly braces}.
		(\{)                     # $7: "{" start delimiter.
		((?:ht|f)tps?:\/\/[a-z0-9\-._~!$&\'()*+,;=:\/?#[\]@%]+)  # $8: URL.
		(\})                     # $9: "}" end delimiter.
		| # Alternative 4: URL delimited by <angle brackets>.
		(<|&(?:lt|\#60|\#x3c);)  # $10: "<" start delimiter (or HTML entity).
		((?:ht|f)tps?:\/\/[a-z0-9\-._~!$&\'()*+,;=:\/?#[\]@%]+)  # $11: URL.
		(>|&(?:gt|\#62|\#x3e);)  # $12: ">" end delimiter (or HTML entity).
		| # Alternative 5: URL not delimited by (), [], {} or <>.
		(                        # $13: Prefix proving URL not already linked.
		(?: ^                  # Can be a beginning of line or string, or
		| [^=\s\'"\]]          # a non-"=", non-quote, non-"]", followed by
		) \s*[\'"]?            # optional whitespace and optional quote;
		| [^=\s]\s+              # or... a non-equals sign followed by whitespace.
		)                        # End $13. Non-prelinkified-proof prefix.
		( \b                     # $14: Other non-delimited URL.
		(?:ht|f)tps?:\/\/      # Required literal http, https, ftp or ftps prefix.
		[a-z0-9\-._~!$\'()*+,;=:\/?#[\]@%]+ # All URI chars except "&" (normal*).
		(?:                    # Either on a "&" or at the end of URI.
		(?!                  # Allow a "&" char only if not start of an...
		&(?:gt|\#0*62|\#x0*3e);                  # HTML ">" entity, or
		| &(?:amp|apos|quot|\#0*3[49]|\#x0*2[27]); # a [&\'"] entity if
		[.!&\',:?;]?        # followed by optional punctuation then
		(?:[^a-z0-9\-._~!$&\'()*+,;=:\/?#[\]@%]|$)  # a non-URI char or EOS.
		) &                  # If neg-assertion true, match "&" (special).
		[a-z0-9\-._~!$\'()*+,;=:\/?#[\]@%]* # More non-& URI chars (normal*).
		)*                     # Unroll-the-loop (special normal*)*.
		[a-z0-9\-_~$()*+=\/#[\]@%]  # Last char can\'t be [.!&\',;:?]
		)                        # End $14. Other non-delimited URL.
		/imx';
		$url_replace = '$1$4$7$10$13<a href="$2$5$8$11$14">$2$5$8$11$14</a>$3$6$9$12';

		$string = preg_replace($url_pattern, $url_replace, $text);
		return $string !== '' && $string !== '0' && $string !== [] && $string !== null ? $string : '';
	}

	public static function linkifyHtmlCallback(array $matches): string
	{
		return $matches[2] ?? self::linkify($matches[1]);
	}

	public static function fixImageLinks(string $buffer): string
	{
		$base = Uri::base(true) . '/';

		// Copied from SEF plugin
		// Check for all unknown protocals (a protocol must contain at least one alpahnumeric character followed by a ":").
		$protocols  = '[a-zA-Z0-9\-]+:';
		$attributes = ['href=', 'src=', 'poster='];

		foreach ($attributes as $attribute) {
			if (str_contains((string)$buffer, $attribute)) {
				$regex  = '#\s' . $attribute . '"(?!/|' . $protocols . '|\#|\')([^"]*)"#m';
				$buffer = preg_replace($regex, ' ' . $attribute . '"' . $base . '$1"', (string)$buffer);
			}
		}

		return $buffer !== '' && $buffer !== '0' && $buffer !== [] && $buffer !== null ? $buffer : '';
	}

	public static function increaseMemoryLimit(int $limit): bool
	{
		$memMax = trim(@ini_get('memory_limit'));
		if ($memMax !== '' && $memMax !== '0') {
			$last = strtolower($memMax[strlen($memMax) - 1]);
			switch ($last) {
				case 'g':
					$memMax = (int)$memMax * 1024;
					// Gigabyte
				case 'm':
					$memMax = (int)$memMax * 1024;
					// Megabyte
				case 'k':
					$memMax = (int)$memMax * 1024;
					// Kilobyte
			}

			if ($memMax > $limit) {
				return true;
			}

			@ini_set('memory_limit', $limit);
		}

		return trim(@ini_get('memory_limit')) == $limit;
	}

	public static function getOppositeBWColor(string $color): string
	{
		$color = trim($color, '#');
		$r     = hexdec(substr($color, 0, 2));
		$g     = hexdec(substr($color, 2, 2));
		$b     = hexdec(substr($color, 4, 2));
		$yiq   = (($r * 299) + ($g * 587) + ($b * 114)) / 1000;

		return ($yiq >= 128) ? '000000' : 'ffffff';
	}
}
