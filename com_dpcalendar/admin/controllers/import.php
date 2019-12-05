<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2019 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */
defined('_JEXEC') or die();

class DPCalendarControllerImport extends JControllerLegacy
{
	public function add($data = array())
	{
		JSession::checkToken() or jexit(JText::_('JINVALID_TOKEN'));

		$model = $this->getModel('Import', '', array());
		$model->import();

		$this->setRedirect(JRoute::_('index.php?option=com_dpcalendar&view=tools&layout=import', false), implode('<br>', $model->get('messages')));
	}

	public function patch()
	{
		JSession::checkToken() or jexit(JText::_('JINVALID_TOKEN'));

		if (!JFactory::getUser()->authorise('core.manage')) {
			throw new Exception('You need to be an admin to execute this task');
		}

		JLoader::import('joomla.filesystem.file');
		$file = $this->input->files->get('patch', [], 'raw');

		$model = $this->getModel('Import', '', []);

		if (!$model->canPatch()) {
			$this->setRedirect(JRoute::_('index.php?option=com_dpcalendar&view=tools&layout=patch', false), 'Patch executable not available or it is not possible to execute the binary!!',
				'error');

			return;
		}

		if (!$file) {
			$this->setRedirect(JRoute::_('index.php?option=com_dpcalendar&view=tools&layout=patch', false), 'File not found!!', 'error');

			return;
		}

		$filename = JFile::makeSafe($file['name']);

		if (strtolower(JFile::getExt($filename)) != 'patch') {
			$this->setRedirect(JRoute::_('index.php?option=com_dpcalendar&view=tools&layout=patch', false), 'No patch file!!', 'error');

			return;
		}

		JFile::upload($file['tmp_name'], JPATH_ROOT . '/tmp/' . $filename, false, true);

		$files = $model->patch(JPATH_ROOT . '/tmp/' . $filename, $this->input->getBool('revert'));

		JFile::delete(JPATH_ROOT . '/tmp/' . $filename);

		if ($files === false) {
			$this->setRedirect(JRoute::_('index.php?option=com_dpcalendar&view=tools&layout=patch', false), nl2br($model->getError()), 'error');

			return;
		}

		$this->setRedirect(
			JRoute::_('index.php?option=com_dpcalendar&view=tools&layout=patch', false),
			JText::_('COM_DPCALENDAR_CONTROLLER_IMPORT_PATCH_SUCCESS' . ($this->input->getBool('revert') ? '_REVERT' : '')) . implode('<br>', $files)
		);
	}
}
