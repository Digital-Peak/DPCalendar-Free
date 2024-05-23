<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2014 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Component\DPCalendar\Administrator\Controller;

defined('_JEXEC') or die();

use Joomla\CMS\MVC\Controller\FormController;

class ExtcalendarController extends FormController
{
	protected $text_prefix = 'COM_DPCALENDAR_EXTCALENDAR';

	protected function getRedirectToItemAppend($recordId = null, $urlVar = 'id')
	{
		$append = parent::getRedirectToItemAppend($recordId, $urlVar);

		$tmp = $this->app->getInput()->get('dpplugin');
		if ($tmp) {
			$append .= '&dpplugin=' . $tmp;
		}

		return $append;
	}

	protected function getRedirectToListAppend()
	{
		$append = parent::getRedirectToListAppend();

		$tmp = $this->app->getInput()->get('dpplugin');
		if ($tmp) {
			$append .= '&dpplugin=' . $tmp;
		}

		return $append;
	}
}
