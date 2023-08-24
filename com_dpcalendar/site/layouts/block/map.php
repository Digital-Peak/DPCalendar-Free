<?php
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2020 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */
defined('_JEXEC') or die();

use DPCalendar\Helper\DPCalendarHelper;

$document = $displayData['document'];

$provider = DPCalendarHelper::getComponentParameter('map_provider', 'openstreetmap');
$document->addScriptOptions('map.provider', $provider);
if ($provider == 'none') {
	return;
}

$key = DPCalendarHelper::getComponentParameter('map_api_google_jskey', '');
if ($provider == 'google' && !$key) {
	Factory::getApplication()->enqueueMessage(
		"Can't load Google maps without an API key. More information can be found in our documentation at <a href='https://joomla.digital-peak.com' target='_blank'>joomla.digital-peak.com</a>.",
		'warning'
	);

	return;
}

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
$lang      = DPCalendarHelper::getFrLanguage();
if (!in_array($lang, $languages)) {
	$lang = substr($lang, 0, strpos($lang, '-'));
}
if (!in_array($lang, $languages)) {
	$lang = 'en';
}

switch ($provider) {
	case 'google':
		$document->addScriptOptions('map.google.lang', $lang);
		$document->addScriptOptions('map.google.key', $key);
		$document->addScriptOptions('map.tiles.url', 'google');
		break;
	case 'mapbox':
		$document->addScriptOptions(
			'map.tiles.attribution',
			'<a href="https://www.mapbox.com/">&copy; '
			. Text::_('COM_DPCALENDAR_FIELD_CONFIG_INTEGRATION_MAP_PROVIDER_MAPBOX')
			. '</a> | <a href="https://www.openstreetmap.org/">&copy; '
			. Text::_('COM_DPCALENDAR_FIELD_CONFIG_INTEGRATION_MAP_PROVIDER_OPENSTREETMAP') . '</a>'
		);

		$document->addScriptOptions('map.mapbox.token', DPCalendarHelper::getComponentParameter(
			'map_api_mapbox_token',
			'pk.eyJ1IjoibWFwYm94IiwiYSI6ImNpejY4NXVycTA2emYycXBndHRqcmZ3N3gifQ.rJcFIG214AriISLbB6B5aw'
		));
		$document->addScriptOptions('map.tiles.url', 'mapbox');
		break;
	default:
		$document->addScriptOptions(
			'map.tiles.attribution',
			'<a href="https://www.openstreetmap.org/">&copy; ' . Text::_('COM_DPCALENDAR_FIELD_CONFIG_INTEGRATION_MAP_PROVIDER_OPENSTREETMAP') . '</a>'
		);

		$document->addScriptOptions(
			'map.tiles.url',
			DPCalendarHelper::getComponentParameter('map_api_openstreetmap_tiles_url', 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png')
		);
}

$displayData['translator']->translateJS('COM_DPCALENDAR_LEAFLET_TEXT_TOUCH');
$displayData['translator']->translateJS('COM_DPCALENDAR_LEAFLET_TEXT_SCROLL');
$displayData['translator']->translateJS('COM_DPCALENDAR_LEAFLET_TEXT_SCROLLMAC');
$displayData['translator']->translateJS('COM_DPCALENDAR_FIELD_CONFIG_INTEGRATION_MAP_PROVIDER_OPENSTREETMAP');
$displayData['translator']->translateJS('COM_DPCALENDAR_FIELD_CONFIG_INTEGRATION_MAP_CONSENT_INFO_TEXT');
