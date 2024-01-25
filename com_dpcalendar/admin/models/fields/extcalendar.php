<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2014 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

defined('_JEXEC') or die();

use DPCalendar\HTML\Document\HtmlDocument;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\FormField;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;

JLoader::import('components.com_dpcalendar.helpers.dpcalendar', JPATH_ADMINISTRATOR);

if (!class_exists('DPCalendarHelper')) {
	return;
}

class JFormFieldExtcalendar extends FormField
{
	public $element;
	public $id;
	protected $type = 'Extcalendar';

	public function getInput()
	{
		Factory::getSession()->set('extcalendarOrigin', Uri::getInstance()->toString(), 'DPCalendar');

		(new HtmlDocument())->loadScriptFile('dpcalendar/fields/extcalendar.js');
		Factory::getDocument()->addStyleDeclaration('#general .controls {margin-left: 0} #general .control-label {width: 0}');

		$url = 'index.php?option=com_dpcalendar&view=extcalendars';
		$url .= '&dpplugin=' . $this->element['plugin'];
		$url .= '&import=' . $this->element['import'];
		$url .= '&tmpl=component';

		return '<iframe src="' . Route::_($url) . '" style="width:100%; border:0"m id="' . $this->id . '" name="' . $this->id . '"></iframe>';
	}
}
