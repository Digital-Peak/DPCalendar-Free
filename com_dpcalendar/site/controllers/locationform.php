<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2014 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */
defined('_JEXEC') or die();

JLoader::import('controllers.location', JPATH_COMPONENT_ADMINISTRATOR);

class DPCalendarControllerLocationForm extends DPCalendarControllerLocation
{
	protected $view_item = 'locationform';

	public function __construct($config = [])
	{
		JModelLegacy::addIncludePath(JPATH_COMPONENT_ADMINISTRATOR . '/models');
		JTable::addIncludePath(JPATH_COMPONENT_ADMINISTRATOR . '/tables');
		JForm::addFormPath(JPATH_ADMINISTRATOR . '/components/com_dpcalendar/models/forms');

		parent::__construct();
	}

	protected function allowDelete($data = [], $key = 'l_id')
	{
		$location = null;

		$recordId = (int)isset($data[$key]) ? $data[$key] : 0;
		if ($recordId) {
			$location = $this->getModel()->getItem($recordId);
		}

		if ($location != null && $location->id) {
			return $location->params->get('access-delete');
		}

		// Since there is no asset tracking, revert to the component permissions
		return JFactory::getUser()->authorise('core.delete', $this->option);
	}

	protected function allowEdit($data = [], $key = 'l_id')
	{
		$location = null;

		$recordId = (int)isset($data[$key]) ? $data[$key] : 0;
		if ($recordId) {
			$location = $this->getModel()->getItem($recordId);
		}

		if ($location != null && $location->id) {
			return $location->params->get('access-edit');
		}

		// Since there is no asset tracking, revert to the component permissions
		return parent::allowEdit($data, $key);
	}

	public function save($key = null, $urlVar = 'l_id')
	{
		$result = parent::save($key, $urlVar);

		if ($return = $this->input->get('return', null, 'base64')) {
			$this->setRedirect(base64_decode($return));
		} else if ($result) {
			$this->setRedirect(
				DPCalendarHelperRoute::getLocationRoute($this->getModel()->getItem(JFactory::getApplication()->getUserState('dpcalendar.location.id')))
			);
		} else {
			$this->setRedirect(JUri::base());
		}

		return $result;
	}

	public function cancel($key = 'l_id')
	{
		$return = parent::cancel($key);

		// Redirect to the return page.
		$this->setRedirect($this->getReturnPage());

		return $return;
	}

	public function delete($key = 'l_id')
	{
		$recordId = $this->input->getInt($key);

		if (!$this->allowDelete([
			$key => $recordId
		], $key)) {
			$this->setError(JText::_('JLIB_APPLICATION_ERROR_EDIT_NOT_PERMITTED'));
			$this->setMessage($this->getError(), 'error');

			$this->setRedirect($this->getReturnPage());

			return false;
		}

		$this->getModel()->publish($recordId, -2);
		if (!$this->getModel()->delete($recordId)) {
			$this->setError(JText::_('JLIB_APPLICATION_ERROR_EDIT_NOT_PERMITTED'));
			$this->setMessage($this->getModel()
				->getError(), 'error');

			$this->setRedirect($this->getReturnPage());

			return false;
		}

		// Redirect to the return page.
		$this->setRedirect($this->getReturnPage(), JText::_('COM_DPCALENDAR_DELETE_SUCCESS'), 'success');

		return true;
	}

	public function getModel($name = 'Location', $prefix = '', $config = ['ignore_request' => true])
	{
		return parent::getModel($name, $prefix, $config);
	}

	public function edit($key = 'id', $urlVar = 'l_id')
	{
		return parent::edit($key, $urlVar);
	}

	protected function getRedirectToItemAppend($recordId = null, $urlVar = null)
	{
		$append = parent::getRedirectToItemAppend($recordId, $urlVar);
		$itemId = $this->input->getInt('Itemid');

		if ($itemId) {
			$append .= '&Itemid=' . $itemId;
		}

		if ($this->input->getCmd('tmpl')) {
			$append .= '&tmpl=' . $this->input->getCmd('tmpl');
		}

		return $append;
	}

	protected function getReturnPage()
	{
		$return = $this->input->getBase64('return');

		if (empty($return) || !JUri::isInternal(base64_decode($return))) {
			return JURI::base();
		} else {
			return base64_decode($return);
		}
	}
}
