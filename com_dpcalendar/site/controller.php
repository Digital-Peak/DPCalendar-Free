<?php

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;

/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2014 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */
defined('_JEXEC') or die();

JLoader::import('joomla.application.component.controller');

class DPCalendarController extends BaseController
{
	public function display($cachable = false, $urlparams = false)
	{
		$cachable = true;
		$user     = Factory::getUser();

		$id    = Factory::getApplication()->input->get('e_id');
		$vName = Factory::getApplication()->input->getCmd('view', 'calendar');
		Factory::getApplication()->input->set('view', $vName);

		if ($user->get('id') || ($_SERVER['REQUEST_METHOD'] == 'POST' && $vName = 'list') || $vName = 'events') {
			$cachable = false;
		}

		$safeurlparams = [
			'id'               => 'STRING',
			'limit'            => 'UINT',
			'limitstart'       => 'UINT',
			'filter_order'     => 'CMD',
			'filter_order_Dir' => 'CMD',
			'lang'             => 'CMD'
		];

		// Check for edit form.
		if ($vName == 'form' && is_numeric($id) && !$this->checkEditId('com_dpcalendar.edit.event', $id)) {
			// Somehow the person just went to the form - we don't allow that.
			throw new Exception(Text::sprintf('JLIB_APPLICATION_ERROR_UNHELD_ID', $id), 403);
		}

		return parent::display($cachable, $safeurlparams);
	}
}
