<?php
/**
 * @package   DPCalendar
 * @author    Digital Peak http://www.digital-peak.com
 * @copyright Copyright (C) 2007 - 2020 Digital Peak. All rights reserved.
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
