<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2019 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Component\DPCalendar\Administrator\Controller;

\defined('_JEXEC') or die();

use Joomla\CMS\MVC\Controller\FormController;

class CountryController extends FormController
{
	protected $text_prefix = 'COM_DPCALENDAR_COUNTRY    ';

	public function save($key = null, $urlVar = 'c_id')
	{
		return parent::save($key, $urlVar);
	}

	public function edit($key = null, $urlVar = 'c_id')
	{
		return parent::edit($key, $urlVar);
	}

	public function cancel($key = 'c_id')
	{
		return parent::cancel($key);
	}
}
