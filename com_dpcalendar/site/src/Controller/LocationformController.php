<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2014 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Component\DPCalendar\Site\Controller;

defined('_JEXEC') or die();

use DigitalPeak\Component\DPCalendar\Administrator\Controller\LocationController;
use DigitalPeak\Component\DPCalendar\Site\Helper\RouteHelper;
use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Form\FormFactoryInterface;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\User\CurrentUserInterface;
use Joomla\CMS\User\CurrentUserTrait;
use Joomla\Input\Input;
use Joomla\Registry\Registry;

class LocationformController extends LocationController implements CurrentUserInterface
{
	use CurrentUserTrait;

	protected $view_item = 'locationform';

	public function __construct(
		$config = [],
		MVCFactoryInterface $factory = null,
		?CMSApplication $app = null,
		?Input $input = null,
		FormFactoryInterface $formFactory = null
	) {
		Form::addFormPath(JPATH_ADMINISTRATOR . '/components/com_dpcalendar/forms');
		parent::__construct($config, $factory, $app, $input, $formFactory);
	}

	protected function allowDelete(array $data = [], string $key = 'l_id'): bool
	{
		$location = null;

		$recordId = (int)isset($data[$key]) !== 0 ? $data[$key] : 0;
		if ($recordId) {
			$location = $this->getModel('Location')->getItem($recordId);
		}

		if ($location != null && $location->id) {
			return (bool)$location->params->get('access-delete');
		}

		// Since there is no asset tracking, revert to the component permissions
		return $this->getCurrentUser()->authorise('core.delete', $this->option);
	}

	protected function allowEdit($data = [], $key = 'l_id')
	{
		$location = null;

		$recordId = (int)isset($data[$key]) !== 0 ? $data[$key] : 0;
		if ($recordId) {
			$location = $this->getModel('Location')->getItem($recordId);
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
		if (!$result) {
			return $result;
		}

		$return = $this->getReturnPage();
		if ($return !== Uri::base()) {
			$this->setRedirect($return);

			return $result;
		}

		$params = $this->getModel('Location', 'Administrator', ['ignore_request' => false])->getState('params', new Registry());
		if ($redirect = $params->get('location_form_redirect')) {
			$article = $this->app->bootComponent('content')->getMVCFactory()->createTable('Article', 'Administrator');
			$article->load($redirect);

			if (!empty($article->id) && !empty($article->catid)) {
				$this->setRedirect(Route::_('index.php?option=com_content&view=article&id=' . $article->id . '&catid=' . $article->catid));

				return $result;
			}
		}

		if ($location = $this->getModel('Location')->getItem($this->app->getUserState('dpcalendar.location.id'))) {
			$this->setRedirect(RouteHelper::getLocationRoute($location));
		}

		return $result;
	}

	public function cancel($key = 'l_id')
	{
		$success = parent::cancel($key);
		$return  = $this->getReturnPage();

		$params = $this->getModel('Location', 'Administrator', ['ignore_request' => false])->getState('params', new Registry());
		if ($return === Uri::base() && $redirect = $params->get('location_form_redirect')) {
			$article = $this->app->bootComponent('content')->getMVCFactory()->createTable('Article', 'Administrator');
			$article->load($redirect);

			if (!empty($article->id) && !empty($article->catid)) {
				$this->setRedirect(Route::_('index.php?option=com_content&view=article&id=' . $article->id . '&catid=' . $article->catid));

				return $success;
			}
		}

		// Redirect to the return page
		$this->setRedirect($return);

		return $success;
	}

	public function delete(string $key = 'l_id'): bool
	{
		$recordId = $this->input->getInt($key, 0);

		if (!$this->allowDelete([$key => $recordId], $key)) {
			throw new \Exception(Text::_('JLIB_APPLICATION_ERROR_EDIT_NOT_PERMITTED'));
		}

		$this->getModel('Location')->publish($recordId, -2);
		if (!$this->getModel('Location')->delete($recordId)) {
			throw new \Exception(Text::_('JLIB_APPLICATION_ERROR_EDIT_NOT_PERMITTED'));
		}

		// Redirect to the return page.
		$this->setRedirect($this->getReturnPage(), Text::_('COM_DPCALENDAR_DELETE_SUCCESS'), 'success');

		return true;
	}

	public function getModel($name = 'Location', $prefix = 'Administrator', $config = ['ignore_request' => true])
	{
		return parent::getModel($name, $prefix, $config);
	}

	public function edit($key = 'id', $urlVar = 'l_id')
	{
		return parent::edit($key, $urlVar);
	}

	protected function getRedirectToItemAppend($recordId = null, $urlVar = 'id')
	{
		$append = parent::getRedirectToItemAppend($recordId, $urlVar);
		$itemId = $this->input->getInt('Itemid', 0);

		if ($itemId !== 0) {
			$append .= '&Itemid=' . $itemId;
		}

		if ($this->input->get('tmpl', '') !== '') {
			$append .= '&tmpl=' . $this->input->get('tmpl', '');
		}

		return $append;
	}

	protected function getReturnPage(): string
	{
		$return = $this->input->getBase64('return');

		if (empty($return) || !Uri::isInternal(base64_decode($return))) {
			return Uri::base();
		}

		return base64_decode($return);
	}
}
