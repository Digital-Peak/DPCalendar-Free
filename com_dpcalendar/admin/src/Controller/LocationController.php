<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2014 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Component\DPCalendar\Administrator\Controller;

defined('_JEXEC') or die();

use DigitalPeak\Component\DPCalendar\Administrator\Helper\DPCalendarHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;

class LocationController extends FormController
{
	protected $text_prefix = 'COM_DPCALENDAR_LOCATION';

	public function batch($model = null)
	{
		Session::checkToken() or jexit(Text::_('JINVALID_TOKEN'));

		// Set the model
		$model = $this->getModel();

		// Preset the redirect
		$this->setRedirect(Route::_('index.php?option=com_dpcalendar&view=locations' . $this->getRedirectToListAppend(), false));

		return parent::batch($model);
	}

	public function save($key = null, $urlVar = 'l_id')
	{
		return parent::save($key, $urlVar);
	}

	public function loc(): void
	{
		Session::checkToken() or jexit(Text::_('JINVALID_TOKEN'));

		$loc = $this->app->bootComponent('dpcalendar')->getMVCFactory()->createModel('Geo', 'Administrator')->getLocation($this->input->getString('loc', ''), false);

		$data              = (array)$loc;
		$data['formatted'] = $this->app->bootComponent('dpcalendar')->getMVCFactory()->createModel('Geo', 'Administrator')->format([$loc]);
		DPCalendarHelper::sendMessage('', false, $data);
	}

	public function searchloc(): void
	{
		Session::checkToken() or jexit(Text::_('JINVALID_TOKEN'));

		DPCalendarHelper::sendMessage('', false, $this->app->bootComponent('dpcalendar')->getMVCFactory()->createModel('Geo', 'Administrator')->search(trim($this->input->getString('loc', ''))));
	}

	public function edit($key = null, $urlVar = 'l_id')
	{
		return parent::edit($key, $urlVar);
	}

	public function cancel($key = 'l_id')
	{
		return parent::cancel($key);
	}
}
