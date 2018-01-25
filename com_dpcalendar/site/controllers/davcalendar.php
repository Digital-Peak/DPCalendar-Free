<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2018 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */
defined('_JEXEC') or die();

JLoader::import('joomla.application.component.controllerform');

class DPCalendarControllerDavcalendar extends JControllerForm
{
	protected $view_item = 'davcalendar';
	protected $view_list = 'profile';
	protected $option = 'com_dpcalendar';
	protected $context = 'davcalendar';
	protected $text_prefix = 'COM_DPCALENDAR_VIEW_DAVCALENDAR';

	public function add()
	{
		if (!parent::add()) {
			// Redirect to the return page.
			$this->setRedirect($this->getReturnPage());
		}
	}

	protected function allowAdd($data = array())
	{
		return true;
	}

	protected function allowEdit($data = array(), $key = 'id')
	{
		$recordId = isset($data[$key]) ? $data[$key] : 0;
		$calendar = $this->getModel()->getItem($recordId);
		if (empty($calendar)) {
			return false;
		}

		return $calendar->principaluri == 'principals/' . JFactory::getUser()->username;
	}

	protected function allowDelete($data = array(), $key = 'id')
	{
		$recordId = isset($data[$key]) ? $data[$key] : 0;
		$calendar = $this->getModel()->getItem($recordId);
		if (empty($calendar)) {
			return false;
		}

		return $calendar->principaluri == 'principals/' . JFactory::getUser()->username;
	}

	public function edit($key = 'id', $urlVar = 'c_id')
	{
		return parent::edit($key, $urlVar);
	}

	public function cancel($key = 'c_id')
	{
		parent::cancel($key);

		// Redirect to the return page.
		$this->setRedirect($this->getReturnPage());
	}

	public function delete($key = 'c_id')
	{
		$recordId = JFactory::getApplication()->input->getVar($key);

		if (!$this->allowDelete(array($key => $recordId), $key)) {
			$this->setError(JText::_('JLIB_APPLICATION_ERROR_EDIT_NOT_PERMITTED'));
			$this->setMessage($this->getError(), 'error');

			$this->setRedirect(
				JRoute::_('index.php?option=' . $this->option . '&view=' . $this->view_list . $this->getRedirectToListAppend(), false)
			);

			return false;
		}

		$table = $this->getModel()->getTable();
		$table->delete($recordId);

		$this->setRedirect($this->getReturnPage(), JText::_('COM_DPCALENDAR_DELETE_SUCCESS'));

		return true;
	}

	public function save($key = null, $urlVar = 'c_id')
	{
		$result = parent::save($key, $urlVar);

		if ($result) {
			$this->setRedirect($this->getReturnPage());
		}

		return $result;
	}

	public function getModel($name = 'davcalendar', $prefix = '', $config = array('ignore_request' => true))
	{
		return parent::getModel($name, $prefix, $config);
	}

	protected function getRedirectToItemAppend($recordId = null, $urlVar = null)
	{
		$append = parent::getRedirectToItemAppend($recordId, $urlVar);
		$itemId = JFactory::getApplication()->input->getInt('Itemid');
		$return = $this->getReturnPage();

		if ($itemId) {
			$append .= '&Itemid=' . $itemId;
		}

		if ($return) {
			$append .= '&return=' . base64_encode($return);
		}

		return $append;
	}

	protected function getReturnPage()
	{
		$return = JFactory::getApplication()->input->get('return', null, 'default', 'base64');

		if (empty($return) || !JUri::isInternal(base64_decode($return))) {
			return JURI::base();
		}

		return base64_decode($return);
	}
}
