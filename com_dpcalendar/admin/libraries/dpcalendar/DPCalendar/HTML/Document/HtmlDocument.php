<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2018 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DPCalendar\HTML\Document;

defined('_JEXEC') or die();

use DPCalendar\Helper\DPCalendarHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;

/**
 * Html document.
 */
class HtmlDocument
{
	public function loadScriptFile($path, string $extension = 'com_dpcalendar'): void
	{
		if (strpos($path, '//') === 0 || strpos($path, 'https://') === 0) {
			Factory::getDocument()->addScript($path, [], ['defer' => true]);

			return;
		}

		static $coreLoaded = false;
		if (!$coreLoaded) {
			$coreLoaded = true;
			// Load core
			HTMLHelper::_('behavior.core');

			// Load DPCalendar loader
			$this->loadScriptFile('dpcalendar/loader.js');
		}

		$path = str_replace('.js', '.min.js', $path);
		HTMLHelper::_('script', $extension . '/' . $path, ['relative' => true, 'version' => JDEBUG ? false : 'auto'], ['defer' => true]);
	}

	public function addScript($content): void
	{
		Factory::getApplication()->getDocument()->addScriptDeclaration($content);
	}

	public function addScriptOptions(string $key, $options): void
	{
		Factory::getApplication()->getDocument()->addScriptOptions('DPCalendar.' . $key, $options);
	}

	public function loadStyleFile($path, string $extension = 'com_dpcalendar'): void
	{
		$path = str_replace('.css', '.min.css', $path);
		HTMLHelper::_('stylesheet', $extension . '/' . $path, ['relative' => true, 'version' => JDEBUG ? false : 'auto']);
	}

	public function addStyle($content): void
	{
		if (!$content) {
			return;
		}

		Factory::getApplication()->getDocument()->addStyleDeclaration($content);
	}

	private function getGoogleLanguage()
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
		$lang = DPCalendarHelper::getFrLanguage();
		if (!in_array($lang, $languages)) {
			$lang = substr($lang, 0, strpos($lang, '-'));
		}
		if (!in_array($lang, $languages)) {
			return 'en';
		}

		return $lang;
	}
}
