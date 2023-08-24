<?php

use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Router\Route;

/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2014 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */
defined('_JEXEC') or die();

class DPCalendarController extends BaseController
{
	public function display($cachable = false, $urlparams = false)
	{
		$view = $this->input->get('view');
		if (!$view && $this->input->get->get('filter')) {
			$view = 'events';
		} elseif (!$view) {
			$view = 'cpanel';
		}

		$this->input->set('view', $view);
		$layout = $this->input->getCmd('layout', 'default');
		$id     = $this->input->getInt('id');

		if ($view != 'event' && $view != 'location' && $view != 'booking') {
			DPCalendarHelper::addSubmenu($this->input->getCmd('view', 'cpanel'));
		}

		// Check for edit form.
		if ($view == 'event' && $layout == 'edit' && !$this->checkEditId('com_dpcalendar.edit.event', $id)) {
			// Somehow the person just went to the form - we don't allow that.
			$this->setError(Text::sprintf('JLIB_APPLICATION_ERROR_UNHELD_ID', $id));
			$this->setMessage($this->getError(), 'error');
			$this->setRedirect(Route::_('index.php?option=com_dpcalendar&view=events', false));

			return false;
		}

		parent::display();

		return $this;
	}

	public function getModel($name = '', $prefix = 'DPCalendarModel', $config = [])
	{
		if ($name == 'event') {
			$name = 'AdminEvent';
		}

		if ($name == 'events') {
			$name = 'AdminEvents';
		}

		return parent::getModel($name, $prefix, $config);
	}
}
