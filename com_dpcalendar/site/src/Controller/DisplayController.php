<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2014 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Component\DPCalendar\Site\Controller;

defined('_JEXEC') or die();

use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\User\CurrentUserInterface;
use Joomla\CMS\User\CurrentUserTrait;

class DisplayController extends BaseController implements CurrentUserInterface
{
	use CurrentUserTrait;

	public function display($cachable = false, $urlparams = [])
	{
		$id    = $this->input->get('e_id');
		$vName = $this->input->getCmd('view', 'calendar');
		$this->input->set('view', $vName);

		$safeurlparams = [
			'id'               => 'STRING',
			'limit'            => 'UINT',
			'limitstart'       => 'UINT',
			'filter_order'     => 'CMD',
			'filter_order_Dir' => 'CMD',
			'lang'             => 'CMD'
		];

		// Check for edit form.
		if ($vName == 'form' && is_numeric($id) && !$this->checkEditId('com_dpcalendar.edit.event', (int)$id)) {
			// Somehow the person just went to the form - we don't allow that.
			throw new \Exception(Text::sprintf('JLIB_APPLICATION_ERROR_UNHELD_ID', $id), 403);
		}

		// The views are not cacheable because the ids are stored in the params
		return parent::display(false, $safeurlparams);
	}
}
