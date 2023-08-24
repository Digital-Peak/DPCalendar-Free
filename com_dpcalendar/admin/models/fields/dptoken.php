<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2017 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

defined('_JEXEC') or die();

use DPCalendar\HTML\Document\HtmlDocument;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Field\TextField;
use Joomla\CMS\Form\FormHelper;
use Joomla\CMS\Language\Text;

if (version_compare(JVERSION, 4, '<') && !class_exists('\\Joomla\\CMS\\Form\\Field\\TextField', false)) {
	FormHelper::loadFieldClass('text');
	class_alias('JFormFieldText', '\\Joomla\\CMS\\Form\\Field\\TextField');
}

class JFormFieldDptoken extends TextField
{
	protected $type = 'Dptoken';

	public function getInput()
	{
		if (!JLoader::import('components.com_dpcalendar.helpers.dpcalendar', JPATH_ADMINISTRATOR)) {
			return '';
		}

		$doc = new HtmlDocument();
		$doc->loadScriptFile('dpcalendar/fields/dptoken.js');
		$doc->loadStyleFile('dpcalendar/fields/dptoken.css');

		// Load the language
		Factory::getLanguage()->load('com_dpcalendar', JPATH_ADMINISTRATOR . '/components/com_dpcalendar');

		$buffer = '<div class="dp-token-container">';
		$buffer .= parent::getInput();
		$buffer .= '<button class="dp-button dp-token-gen">' . htmlspecialchars(Text::_('COM_DPCALENDAR_GENERATE')) . '</button>';
		$buffer .= '<button class="dp-button dp-token-clear">' . htmlspecialchars(Text::_('JCLEAR')) . '</button>';
		$buffer .= '</div>';

		return $buffer;
	}
}
