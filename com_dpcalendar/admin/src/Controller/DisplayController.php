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

class DisplayController extends BaseController
{
	public function display($cachable = false, $urlparams = []): static
	{
		$view = $this->input->get('view');
		if (!$view && $this->input->get->get('filter')) {
			$view = 'events';
		} elseif (!$view) {
			$view = 'cpanel';
		}

		$this->input->set('view', $view);
		$layout = $this->input->getCmd('layout', 'default');
		$id     = $this->input->getInt('id', 0);

		if ($view != 'event' && $view != 'location' && $view != 'booking') {
			//DPCalendarHelper::addSubmenu($this->input->getCmd('view', 'cpanel'));
		}

		// Check for edit form.
		if ($view == 'event' && $layout == 'edit' && !$this->checkEditId('com_dpcalendar.edit.event', $id)) {
			// Somehow the person just went to the form - we don't allow that.
			throw new \Exception(Text::sprintf('JLIB_APPLICATION_ERROR_UNHELD_ID', $id));
		}

		parent::display();

		return $this;
	}
}
