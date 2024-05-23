<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2018 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Component\DPCalendar\Administrator\Translator;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Language;
use Joomla\CMS\Language\Text;

class Translator
{
	private readonly Language $language;

	public function __construct(Language $language = null)
	{
		if (!$language instanceof Language) {
			$language = Factory::getApplication()->getLanguage();
		}

		$this->language = $language;
	}

	public function translate(string $string): string
	{
		return $this->language->_($string);
	}

	public function translateJS(string $string): void
	{
		Text::script($string);
	}
}
