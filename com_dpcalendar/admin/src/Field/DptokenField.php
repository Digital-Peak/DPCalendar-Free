<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2017 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Component\DPCalendar\Administrator\Field;

\defined('_JEXEC') or die();

use DigitalPeak\Component\DPCalendar\Administrator\HTML\Document\HtmlDocument;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Field\TextField;
use Joomla\CMS\Language\Text;

class DptokenField extends TextField
{
	protected $type = 'Dptoken';

	protected function getInput(): string
	{
		$doc = new HtmlDocument();
		$doc->loadScriptFile('dpcalendar/fields/dptoken.js');
		$doc->loadStyleFile('dpcalendar/fields/dptoken.css');

		// Load the language
		Factory::getApplication()->getLanguage()->load('com_dpcalendar', JPATH_ADMINISTRATOR . '/components/com_dpcalendar');

		$buffer = '<div class="dp-token-container">';
		$buffer .= parent::getInput();
		$buffer .= '<button class="dp-button dp-token-gen">' . htmlspecialchars(Text::_('COM_DPCALENDAR_GENERATE')) . '</button>';
		$buffer .= '<button class="dp-button dp-token-clear">' . htmlspecialchars(Text::_('JCLEAR')) . '</button>';

		return $buffer . '</div>';
	}
}
