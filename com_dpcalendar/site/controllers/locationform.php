<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2014 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

defined('_JEXEC') or die();

use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Uri\Uri;
use Joomla\Registry\Registry;

JLoader::import('controllers.location', JPATH_ADMINISTRATOR . '/components/com_dpcalendar');

class DPCalendarControllerLocationForm extends DPCalendarControllerLocation
{
	protected $view_item = 'locationform';

	public function __construct($config = [])
	{
		BaseDatabaseModel::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_dpcalendar/models');
		Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_dpcalendar/tables');
		Form::addFormPath(JPATH_ADMINISTRATOR . '/components/com_dpcalendar/models/forms');

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
		return Factory::getUser()->authorise('core.delete', $this->option);
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

		$return = $this->getReturnPage();
		if ($return !== Uri::base()) {
			$this->setRedirect(base64_decode($return));

			return $result;
		}

		if (!$result) {
			$this->setRedirect(Uri::base());

			return $result;
		}

		$params = $this->getModel('Location', 'DPCalendarModel', ['ignore_request' => false])->getState('params', new Registry());
		if ($redirect = $params->get('location_form_redirect')) {
			$article = Table::getInstance('Content');
			$article->load($redirect);

			if ($article->id) {
				$this->setRedirect(Route::_('index.php?option=com_content&view=article&id=' . $article->id . '&catid=' . $article->catid));

				return $result;
			}
		}

		$this->setRedirect(
			DPCalendarHelperRoute::getLocationRoute($this->getModel()->getItem(Factory::getApplication()->getUserState('dpcalendar.location.id')))
		);

		return $result;
	}

	public function cancel($key = 'l_id')
	{
		$success = parent::cancel($key);
		$return  = $this->getReturnPage();

		$params = $this->getModel('Location', 'DPCalendarModel', ['ignore_request' => false])->getState('params', new Registry());
		if ($return === Uri::base() && $redirect = $params->get('location_form_redirect')) {
			$article = Table::getInstance('Content');
			$article->load($redirect);

			if ($article->id) {
				$this->setRedirect(Route::_('index.php?option=com_content&view=article&id=' . $article->id . '&catid=' . $article->catid));

				return $success;
			}
		}

		// Redirect to the return page
		$this->setRedirect($return);

		return $success;
	}

	public function delete($key = 'l_id')
	{
		$recordId = $this->input->getInt($key);

		if (!$this->allowDelete([
			$key => $recordId
		], $key)) {
			$this->setError(Text::_('JLIB_APPLICATION_ERROR_EDIT_NOT_PERMITTED'));
			$this->setMessage($this->getError(), 'error');

			$this->setRedirect($this->getReturnPage());

			return false;
		}

		$this->getModel()->publish($recordId, -2);
		if (!$this->getModel()->delete($recordId)) {
			$this->setError(Text::_('JLIB_APPLICATION_ERROR_EDIT_NOT_PERMITTED'));
			$this->setMessage($this->getModel()
				->getError(), 'error');

			$this->setRedirect($this->getReturnPage());

			return false;
		}

		// Redirect to the return page.
		$this->setRedirect($this->getReturnPage(), Text::_('COM_DPCALENDAR_DELETE_SUCCESS'), 'success');

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

		if (empty($return) || !Uri::isInternal(base64_decode($return))) {
			return Uri::base();
		}

		return base64_decode($return);
	}
}
