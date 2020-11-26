<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2018 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */
namespace DPCalendar\Translator;

class Translator
{
	public function translate($string)
	{
		return \JText::_($string);
	}

	public function translateJS($string)
	{
		\JText::script($string);
	}
}
