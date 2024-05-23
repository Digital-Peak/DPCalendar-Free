<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2014 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Component\DPCalendar\Administrator\Field;

defined('_JEXEC') or die();

use DigitalPeak\Component\DPCalendar\Administrator\HTML\Document\HtmlDocument;
use DigitalPeak\Component\DPCalendar\Administrator\Translator\Translator;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Field\TextField;
use Joomla\CMS\Language\Text;

class GeocompleteField extends TextField
{
	protected $type = 'Geocomplete';

	protected function getInput(): string
	{
		Factory::getApplication()->bootComponent('dpcalendar')->getMVCFactory()->createModel('Layout', 'Administrator')->renderLayout(
			'block.map',
			['document' => new HtmlDocument(), 'translator' => new Translator()]
		);

		$input = parent::getInput();

		return $input . ('<button id="' . $this->id . '_find" class="dp-button dp-button-action" type="button" title="' . Text::_('JSEARCH_FILTER_SUBMIT') .
			'"><i class="icon-search"></i></button>');
	}
}
