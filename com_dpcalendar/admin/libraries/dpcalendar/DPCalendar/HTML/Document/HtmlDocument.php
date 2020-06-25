<?php
/**
 * @package   DPCalendar
 * @author    Digital Peak http://www.digital-peak.com
 * @copyright Copyright (C) 2007 - 2020 Digital Peak. All rights reserved.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
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
	const LIBRARY_TOOLTIP = 'tooltip';
	const LIBRARY_FORM = 'form';
	const LIBRARY_IFRAME_CHILD = 'iframe-child';
	const LIBRARY_IFRAME_PARENT = 'iframe-parent';

	/**
	 * @param $name
	 *
	 * @deprecated Scripts are loaded in layout files and dependencyies in JS
	 */
	public function loadLibrary($name)
	{
	}

	public function loadScriptFile($path, $extension = 'com_dpcalendar')
	{
		if (strpos($path, '//') === 0 || strpos($path, 'https://') === 0) {
			\JFactory::getDocument()->addScript($path, [], ['defer' => true]);

			return;
		}

		static $coreLoaded = false;
		if (!$coreLoaded) {
			$coreLoaded = true;
			// Load core
			\JHtml::_('behavior.core');

			// Load polyfill for IE
			$ua = htmlentities($_SERVER['HTTP_USER_AGENT'], ENT_QUOTES, 'UTF-8');
			if (preg_match('~MSIE|Internet Explorer~i', $ua) || (strpos($ua, 'Trident/7.0') !== false && strpos($ua, 'rv:11.0') !== false)) {
				$this->loadScriptFile('dpcalendar/polyfill.js');
				$this->loadScriptFile('/polyfill/promise.js');
			}

			// Load DPCalendar loader
			$this->loadScriptFile('dpcalendar/loader.js');
		}

		$path = str_replace('.js', '.min.js', $path);
		\JHtml::_('script', $extension . '/' . $path, ['relative' => true, 'version' => JDEBUG ? false : 'auto'], ['defer' => true]);
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
		\JHtml::_('stylesheet', $extension . '/' . $path, ['relative' => true, 'version' => JDEBUG ? false : 'auto']);
	}

	public function addStyle($content)
	{
		\JFactory::getApplication()->getDocument()->addStyleDeclaration($content);
	}

	private static function getGoogleLanguage()
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
