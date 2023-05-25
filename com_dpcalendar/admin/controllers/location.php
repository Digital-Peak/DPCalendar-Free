<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2014 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

defined('_JEXEC') or die();

use DPCalendar\Helper\DPCalendarHelper;
use DPCalendar\Helper\Location;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;

class DPCalendarControllerLocation extends FormController
{
	protected $text_prefix = 'COM_DPCALENDAR_LOCATION';

	public function batch($model = null)
	{
		Session::checkToken() or jexit(Text::_('JINVALID_TOKEN'));

		// Set the model
		$model = $this->getModel('Location', '', []);

		// Preset the redirect
		$this->setRedirect(Route::_('index.php?option=com_dpcalendar&view=locations' . $this->getRedirectToListAppend(), false));

		return parent::batch($model);
	}

	public function save($key = null, $urlVar = 'l_id')
	{
		return parent::save($key, $urlVar);
	}

	protected function postSaveHook(BaseDatabaseModel $model, $validData = [])
	{
		$this->id    = $model->getState('location.id');
		$this->error = $model->getError();
	}

	public function loc()
	{
		Session::checkToken() or jexit(Text::_('JINVALID_TOKEN'));

		$loc = Location::get($this->input->getString('loc'), false);

		$data             = (array)$loc;
		$data['formated'] = Location::format([$loc]);
		DPCalendarHelper::sendMessage(null, false, $data);
	}

	public function searchloc()
	{
		Session::checkToken() or jexit(Text::_('JINVALID_TOKEN'));

		DPCalendarHelper::sendMessage(null, false, Location::search(trim($this->input->getString('loc'))));
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
