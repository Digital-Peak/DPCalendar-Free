<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2014 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Component\DPCalendar\Administrator\Field;

\defined('_JEXEC') or die();

use DigitalPeak\Component\DPCalendar\Administrator\HTML\Document\HtmlDocument;
use Joomla\CMS\Application\CMSWebApplicationInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\FormField;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;

class ExtcalendarField extends FormField
{
	protected $type = 'Extcalendar';

	protected function getInput(): string
	{
		$app = Factory::getApplication();
		if (!$app instanceof CMSWebApplicationInterface) {
			return '';
		}

		$app->getSession()->set('com_dpcalendar.extcalendar.origin', Uri::getInstance()->toString());

		(new HtmlDocument())->loadScriptFile('fields/extcalendar.js');
		$app->getDocument()->getWebAssetManager()->addInlineStyle('#general .controls {margin-left: 0} #general .control-label {width: 0}');

		$url = 'index.php?option=com_dpcalendar&view=extcalendars';
		$url .= '&dpplugin=' . $this->element['plugin'];
		$url .= '&import=' . $this->element['import'];
		$url .= '&tmpl=component';

		return '<iframe src="' . Route::_($url) . '" style="width:100%; border:0"m id="' . $this->id . '" name="' . $this->id . '"></iframe>';
	}
}
