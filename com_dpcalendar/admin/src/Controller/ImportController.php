<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2014 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Component\DPCalendar\Administrator\Controller;

defined('_JEXEC') or die();

use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;

class ImportController extends BaseController
{
	public function add(array $data = []): void
	{
		Session::checkToken() or jexit(Text::_('JINVALID_TOKEN'));

		$model = $this->getModel();
		$model->import();

		$this->setRedirect(
			Route::_('index.php?option=com_dpcalendar&view=tools&layout=import', false),
			implode('<br>', $model->getState('messages', []))
		);
	}

	public function geodb(): void
	{
		$model = $this->getModel();

		$message = '';
		try {
			$model->importGeoDB();
		} catch (\Exception $exception) {
			$message = Text::sprintf('COM_DPCALENDAR_CONTROLLER_GEO_IMPORT_ERROR', $exception->getMessage());
		}

		$this->setRedirect(
			Route::_('index.php?option=com_dpcalendar&view=cpanel', false),
			$message,
			$message !== '' && $message !== '0' ? 'error' : null
		);
	}
}
