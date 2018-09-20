<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2018 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */
namespace DPCalendar\HTML\Document;

defined('_JEXEC') or die();

use DPCalendar\Helper\DPCalendarHelper;

/**
 * Html document.
 */
class HtmlDocument
{
	const LIBRARY_CORE = 'core';
	const LIBRARY_DPCORE = 'dpcore';
	const LIBRARY_FULLCALENDAR = 'fullcalendar';
	const LIBRARY_SCHEDULER = 'scheduler';
	const LIBRARY_MODAL = 'modal';
	const LIBRARY_MAP = 'map';
	const LIBRARY_MD5 = 'md5';
	const LIBRARY_URL = 'url';
	const LIBRARY_MOMENT = 'moment';
	const LIBRARY_DATEPICKER = 'date';
	const LIBRARY_AUTOCOMPLETE = 'autocomplete';
	const LIBRARY_SELECT = 'select';
	const LIBRARY_FORM = 'form';
	const LIBRARY_IFRAME_CHILD = 'iframe-child';
	const LIBRARY_IFRAME_PARENT = 'iframe-parent';

	public function loadLibrary($name)
	{
		if ($name == self::LIBRARY_CORE) {
			\JHtml::_('behavior.core');
		}
		if ($name == self::LIBRARY_FORM) {
			\JHtml::_('behavior.keepalive');
			\JHtml::_('behavior.formvalidator');

			if (\JFactory::getApplication()->isClient('administrator')) {
				\JHtml::_('behavior.tabstate');
			}
		}
		if ($name == self::LIBRARY_SELECT) {
			$this->loadScriptFile('slim-select/slimselect.js');
			$this->loadStyleFile('slim-select/slimselect.css');
		}
		if ($name == self::LIBRARY_DPCORE) {
			\JHtml::_('behavior.core');
			$this->loadScriptFile('dpcalendar/dpcalendar.js');
		}
		if ($name == self::LIBRARY_URL) {
			$this->loadScriptFile('domurl/url.js');
		}
		if ($name == self::LIBRARY_MOMENT) {
			$this->loadScriptFile('moment/moment.js');
		}
		if ($name == self::LIBRARY_MODAL) {
			$this->loadScriptFile('tingle/tingle.js');
			$this->loadStyleFile('tingle/tingle.css');
		}
		if ($name == self::LIBRARY_AUTOCOMPLETE) {
			$this->loadScriptFile('popper/popper.js');
			$this->loadScriptFile('dpcalendar/layouts/block/autocomplete.js');
		}
		if ($name == self::LIBRARY_MD5) {
			$this->loadScriptFile('md5/md5.js');
		}
		if ($name == self::LIBRARY_FULLCALENDAR) {
			\JHtml::_('behavior.core');
			\JHtml::_('jquery.framework');
			$this->loadScriptFile('dpcalendar/dpcalendar.js');
			$this->loadScriptFile('popper/popper.js');
			$this->loadScriptFile('tippy/tippy.js');
			$this->loadStyleFile('tippy/tippy.css');

			$this->loadScriptFile('md5/md5.js');
			$this->loadScriptFile('domurl/url.js');
			$this->loadScriptFile('moment/moment.js');
			$this->loadScriptFile('fullcalendar/fullcalendar.js');
			$this->loadStyleFile('fullcalendar/fullcalendar.css');

			$this->loadScriptFile('dpcalendar/calendar.js');
		}
		if ($name == self::LIBRARY_SCHEDULER) {
			\JHtml::_('behavior.core');
			\JHtml::_('jquery.framework');
			$this->loadScriptFile('dpcalendar/dpcalendar.js');
			$this->loadScriptFile('scheduler/scheduler.js');
			$this->loadStyleFile('scheduler/scheduler.css');
		}
		if ($name == self::LIBRARY_DATEPICKER) {
			$this->loadScriptFile('moment/moment.js');
			$this->loadScriptFile('pikaday/pikaday.js');
			$this->loadStyleFile('pikaday/pikaday.css');
		}
		if ($name == self::LIBRARY_MAP) {
			\JHtml::_('behavior.core');
			$this->loadScriptFile('dpcalendar/dpcalendar.js');

			$provider = DPCalendarHelper::getComponentParameter('map_provider', 'openstreetmap');
			if ($provider == 'google') {
				$key = DPCalendarHelper::getComponentParameter('map_api_google_jskey', '');
				if (!$key) {
					\JFactory::getApplication()->enqueueMessage("Can't load Google maps without an API key. More information can be found in our documentation at <a href='https:://joomla.digital-peak.com'>joomla.digital-peak.com</a>.", 'warning');
				} else {
					$key = '&key=' . $key;
					\JHtml::_(
						'script',
						'https://maps.googleapis.com/maps/api/js?libraries=places&language=' . self::getGoogleLanguage() . $key,
						[],
						['defer' => true]
					);
					$this->loadScriptFile('dpcalendar/map/google.js');
				}
			}

			if ($provider == 'openstreetmap') {
				\JText::script('COM_DPCALENDAR_FIELD_CONFIG_INTEGRATION_MAP_PROVIDER_OPENSTREETMAP');

				$this->loadScriptFile('leaflet/leaflet.js');
				$this->loadStyleFile('leaflet/leaflet.css');
				$this->loadScriptFile('dpcalendar/map/leaflet.js');

				$this->addScriptOptions(
					'map.tiles.attribution',
					'<a href="https://www.openstreetmap.org/">&copy; ' . \JText::_('COM_DPCALENDAR_FIELD_CONFIG_INTEGRATION_MAP_PROVIDER_OPENSTREETMAP') . '</a>'
				);

				$this->addScriptOptions(
					'map.tiles.url',
					DPCalendarHelper::getComponentParameter('map_api_openstreetmap_tiles_url', 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png')
				);
			}

			if ($provider == 'mapbox') {
				\JText::script('COM_DPCALENDAR_FIELD_CONFIG_INTEGRATION_MAP_PROVIDER_MAPBOX');

				$this->loadScriptFile('leaflet/leaflet.js');
				$this->loadStyleFile('leaflet/leaflet.css');
				$this->loadScriptFile('dpcalendar/map/leaflet.js');

				$this->addScriptOptions(
					'map.tiles.attribution',
					'<a href="https://www.mapbox.com/">&copy; '
					. \JText::_('COM_DPCALENDAR_FIELD_CONFIG_INTEGRATION_MAP_PROVIDER_MAPBOX')
					. '</a> | <a href="https://www.openstreetmap.org/">&copy; '
					. \JText::_('COM_DPCALENDAR_FIELD_CONFIG_INTEGRATION_MAP_PROVIDER_OPENSTREETMAP') . '</a>'
				);


				$this->addScriptOptions(
					'map.tiles.url',
					'https://api.tiles.mapbox.com/v4/{id}/{z}/{x}/{y}.png?access_token='
					. DPCalendarHelper::getComponentParameter(
						'map_api_mapbox_token',
						'pk.eyJ1IjoibWFwYm94IiwiYSI6ImNpejY4NXVycTA2emYycXBndHRqcmZ3N3gifQ.rJcFIG214AriISLbB6B5aw'
					)
				);
			}
		}
		if ($name == self::LIBRARY_IFRAME_PARENT) {
			$this->loadScriptFile('iframe-resizer/iframeresizer.js');
		}
		if ($name == self::LIBRARY_IFRAME_CHILD) {
			$this->loadScriptFile('iframe-resizer/iframeresizer-contentwindow.js');
		}
	}

	public function loadScriptFile($path, $extension = 'com_dpcalendar')
	{
		if (strpos($path, '//') === 0 || strpos($path, 'https://') === 0) {
			\JFactory::getDocument()->addScript($path);

			return;
		}

		$path = str_replace('.js', '.min.js', $path);
		\JHtml::_('script', $extension . '/' . $path, ['relative' => true], ['defer' => true]);
	}

	public function addScript($content)
	{
		\JFactory::getApplication()->getDocument()->addScriptDeclaration($content);
	}

	public function addScriptOptions($key, $options)
	{
		\JFactory::getApplication()->getDocument()->addScriptOptions('DPCalendar.' . $key, $options);
	}

	public function loadStyleFile($path, $extension = 'com_dpcalendar')
	{
		$path = str_replace('.css', '.min.css', $path);
		\JHtml::_('stylesheet', $extension . '/' . $path, ['relative' => true]);
	}

	public function addStyle($content)
	{
		\JFactory::getApplication()->getDocument()->addStyleDeclaration($content);
	}

	private static function getGoogleLanguage()
	{
		$languages = array(
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
		);
		$lang      = DPCalendarHelper::getFrLanguage();
		if (!in_array($lang, $languages)) {
			$lang = substr($lang, 0, strpos($lang, '-'));
		}
		if (!in_array($lang, $languages)) {
			$lang = 'en';
		}

		return $lang;
	}
}
