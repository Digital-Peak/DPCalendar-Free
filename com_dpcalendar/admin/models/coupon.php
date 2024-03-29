<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2020 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

defined('_JEXEC') or die();

use DPCalendar\Helper\DPCalendarHelper;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Uri\Uri;

class DPCalendarModelCoupon extends AdminModel
{
	protected $text_prefix = 'COM_DPCALENDAR_COUPON';

	protected function canDelete($record)
	{
		if (!empty($record->id) && $record->state != -2) {
			return false;
		}

		return parent::canDelete($record);
	}

	public function getItem($pk = null)
	{
		$item = parent::getItem($pk);

		$item->calendars = $item->calendars && is_string($item->calendars) ? explode(',', $item->calendars) : [];
		$item->users     = $item->users && is_string($item->users) ? explode(',', $item->users) : [];

		return $item;
	}

	public function getItemByCode($code, $calid = 0, $email = '', $userId = 0): ?object
	{
		if (empty($code)) {
			return null;
		}

		$item = $this->getItem(['code' => $code, 'state' => 1]);
		if (!$item || !$item->id) {
			return null;
		}

		// Check calendars
		if ($item->calendars && !in_array($calid, $item->calendars)) {
			return null;
		}

		// Check mail
		if ($item->emails && !in_array($email, explode(PHP_EOL, $item->emails))) {
			return null;
		}

		// Check users
		if ($item->users && !in_array($userId, $item->users)) {
			return null;
		}

		// Check publishing state
		$now = DPCalendarHelper::getDate();
		if ($item->publish_up && $now->toSql() < $item->publish_up) {
			return null;
		}

		if ($item->publish_down && $now->toSql() > $item->publish_down) {
			return null;
		}

		// Check limit
		if ($item->limit) {
			$this->getDbo()->setQuery('select count(id) as total from #__dpcalendar_bookings where coupon_id = ' . (int)$item->id);
			$count = $this->getDbo()->loadAssoc();

			if ($count['total'] >= $item->limit) {
				return null;
			}
		}

		return $item;
	}

	public function getTable($type = 'Coupon', $prefix = 'DPCalendarTable', $config = [])
	{
		return parent::getTable($type, $prefix, $config);
	}

	public function getForm($data = [], $loadData = true, $controlName = 'jform')
	{
		// Get the form.
		$form = $this->loadForm('com_dpcalendar.coupon', 'coupon', ['control' => $controlName, 'load_data' => $loadData]);
		if (empty($form)) {
			return false;
		}

		return $form;
	}

	protected function loadFormData()
	{
		$data = Factory::getApplication()->getUserState('com_dpcalendar.edit.coupon.data', []);
		if (empty($data)) {
			$data = $this->getItem();
		}

		$this->preprocessData('com_dpcalendar.coupon', $data);

		return $data instanceof Table ? $data->getProperties() : $data;
	}

	protected function populateState()
	{
		$app = Factory::getApplication();

		$pk = $app->input->getInt('co_id', 0);
		$this->setState('coupon.id', $pk);
		$this->setState('form.id', $pk);

		$return = $app->input->get('return', '', 'default', 'base64');
		if (!Uri::isInternal(base64_decode($return))) {
			$return = '';
		}

		$this->setState('return_page', base64_decode($return));

		$this->setState('params', method_exists($app, 'getParams') ? $app->getParams() : ComponentHelper::getParams('com_dpcalendar'));
	}

	public function getReturnPage()
	{
		return base64_encode($this->getState('return_page', '') ?: Uri::base(true));
	}
}
