<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2014 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Component\DPCalendar\Site\Controller;

defined('_JEXEC') or die();

use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\User\CurrentUserInterface;
use Joomla\CMS\User\CurrentUserTrait;

class DavcalendarController extends FormController implements CurrentUserInterface
{
	use CurrentUserTrait;

	protected $view_item   = 'davcalendar';
	protected $view_list   = 'profile';
	protected $option      = 'com_dpcalendar';
	protected $context     = 'davcalendar';
	protected $text_prefix = 'COM_DPCALENDAR_VIEW_DAVCALENDAR';

	public function add(): bool
	{
		if (!parent::add()) {
			// Redirect to the return page.
			$this->setRedirect($this->getReturnPage());

			return false;
		}

		return true;
	}

	protected function allowAdd($data = []): bool
	{
		return true;
	}

	protected function allowEdit($data = [], $key = 'id'): bool
	{
		$recordId = $data[$key] ?? 0;
		$calendar = $this->getModel()->getItem($recordId);
		if (empty($calendar)) {
			return false;
		}

		return $calendar->principaluri == 'principals/' . $this->getCurrentUser()->username;
	}

	protected function allowDelete(array $data = [], string  $key = 'id'): bool
	{
		$recordId = $data[$key] ?? 0;
		$calendar = $this->getModel()->getItem($recordId);
		if (empty($calendar)) {
			return false;
		}

		return $calendar->principaluri == 'principals/' . $this->getCurrentUser()->username;
	}

	public function edit($key = 'id', $urlVar = 'c_id')
	{
		return parent::edit($key, $urlVar);
	}

	public function cancel($key = 'c_id')
	{
		$return = parent::cancel($key);

		// Redirect to the return page.
		$this->setRedirect($this->getReturnPage());

		return $return;
	}

	public function delete(?string $key = 'c_id'): bool
	{
		$recordId = $this->app->getInput()->getString($key, '');

		if (!$this->allowDelete([$key => $recordId], $key !== null && $key !== '' && $key !== '0' ? $key : 'c_id')) {
			throw new \Exception(Text::_('JLIB_APPLICATION_ERROR_EDIT_NOT_PERMITTED'));
		}

		$table = $this->getModel()->getTable();
		$table->delete($recordId);

		$this->setRedirect($this->getReturnPage(), Text::_('COM_DPCALENDAR_DELETE_SUCCESS'));

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

	public function getModel($name = 'Davcalendar', $prefix = 'Site', $config = ['ignore_request' => true])
	{
		return parent::getModel($name, $prefix, $config);
	}

	protected function getRedirectToItemAppend($recordId = null, $urlVar = '')
	{
		$append = parent::getRedirectToItemAppend($recordId, $urlVar);
		$itemId = $this->app->getInput()->getInt('Itemid', 0);
		$return = $this->getReturnPage();

		if ($itemId) {
			$append .= '&Itemid=' . $itemId;
		}

		if ($return !== '' && $return !== '0') {
			$append .= '&return=' . base64_encode($return);
		}

		return $append;
	}

	protected function getReturnPage(): string
	{
		$return = $this->app->getInput()->get('return', null, 'base64');

		if (empty($return) || !Uri::isInternal(base64_decode((string)$return))) {
			return Uri::base();
		}

		return base64_decode((string)$return);
	}
}
