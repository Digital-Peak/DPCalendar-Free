<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2014 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */
defined('_JEXEC') or die();

class DPCalendarControllerImport extends JControllerLegacy
{
	public function add($data = [])
	{
		JSession::checkToken() or jexit(JText::_('JINVALID_TOKEN'));

		$model = $this->getModel('Import', '', []);
		$model->import();

		$this->setRedirect(JRoute::_('index.php?option=com_dpcalendar&view=tools&layout=import', false), implode('<br>', $model->get('messages')));
	}

	public function geodb()
	{
		$model = $this->getModel('Import', '', []);

		$message = '';
		try {
			$model->importGeoDB();
		} catch (Exception $e) {
			$message = JText::sprintf('COM_DPCALENDAR_CONTROLLER_GEO_IMPORT_ERROR', $e->getMessage());
		}
		$this->setRedirect(JRoute::_('index.php?option=com_dpcalendar&view=cpanel', false), $message, $message ? 'error' : null);
	}
}
