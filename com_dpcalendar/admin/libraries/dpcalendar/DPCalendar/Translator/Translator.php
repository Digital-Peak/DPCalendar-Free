<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2018 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DPCalendar\Translator;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Language;
use Joomla\CMS\Language\Text;

class Translator
{
	/**
	 * @var Language $language
	 */
	private $language;

	public function __construct(Language $language = null)
	{
		if (!$language instanceof Language) {
			$language = Factory::getLanguage();
		}

		$this->language = $language;
	}

	public function translate($string)
	{
		return $this->language->_($string);
	}

	public function translateJS($string): void
	{
		Text::script($string);
	}
}
