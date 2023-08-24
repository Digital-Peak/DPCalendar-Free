<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2014 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

defined('_JEXEC') or die();

use DPCalendar\Helper\LayoutHelper;
use DPCalendar\HTML\Document\HtmlDocument;
use DPCalendar\Translator\Translator;
use Joomla\CMS\Form\Field\TextField;
use Joomla\CMS\Form\FormHelper;
use Joomla\CMS\Language\Text;

JLoader::import('components.com_dpcalendar.helpers.dpcalendar', JPATH_ADMINISTRATOR);
if (version_compare(JVERSION, 4, '<') && !class_exists('\\Joomla\\CMS\\Form\\Field\\TextField', false)) {
	FormHelper::loadFieldClass('text');
	class_alias('JFormFieldText', '\\Joomla\\CMS\\Form\\Field\\TextField');
}

class JFormFieldGeocomplete extends TextField
{
	protected $type = 'Geocomplete';

	public function getInput()
	{
		(new LayoutHelper())->renderLayout(
			'block.map',
			['document' => new HtmlDocument(), 'translator' => new Translator()]
		);

		$input = parent::getInput();

		$input .= '<button id="' . $this->id . '_find" class="dp-button dp-button-action" type="button" title="' . Text::_('JSEARCH_FILTER_SUBMIT') .
			'"><i class="icon-search"></i></button>';

		return $input;
	}
}
