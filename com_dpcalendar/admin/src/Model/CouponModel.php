<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2020 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Component\DPCalendar\Administrator\Model;

defined('_JEXEC') or die();

use DigitalPeak\Component\DPCalendar\Administrator\Helper\DPCalendarHelper;
use DigitalPeak\Component\DPCalendar\Administrator\Table\BasicTable;
use Joomla\CMS\Application\CMSWebApplicationInterface;
use Joomla\CMS\Application\SiteApplication;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\Uri\Uri;

class CouponModel extends AdminModel
{
	protected $text_prefix = 'COM_DPCALENDAR_COUPON';

	protected function canDelete($record)
	{
		if (!empty($record->state) && $record->state != -2) {
			return false;
		}

		return parent::canDelete($record);
	}

	public function getItem($pk = null)
	{
		$item = parent::getItem($pk);
		if ($item === false) {
			return $item;
		}

		$item->calendars = $item->calendars && is_string($item->calendars) ? explode(',', $item->calendars) : [];
		$item->users     = $item->users && is_string($item->users) ? explode(',', $item->users) : [];

		return $item;
	}

	public function getItemByCode(string $code, string $calid = '0', string $email = '', string $userId = '0'): ?\stdClass
	{
		if ($code === '' || $code === '0') {
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
		if ($item->emails && !in_array($email, explode(PHP_EOL, (string)$item->emails))) {
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
			$this->getDatabase()->setQuery('select count(id) as total from #__dpcalendar_bookings where coupon_id = ' . (int)$item->id);
			$count = $this->getDatabase()->loadAssoc();

			if ($count['total'] >= $item->limit) {
				return null;
			}
		}

		return (object)(array)$item;
	}

	public function getTable($type = 'Coupon', $prefix = 'Administrator', $config = [])
	{
		return parent::getTable($type, $prefix, $config);
	}

	public function getForm($data = [], $loadData = true, string $controlName = 'jform')
	{
		// Get the form.
		return $this->loadForm('com_dpcalendar.coupon', 'coupon', ['control' => $controlName, 'load_data' => $loadData]);
	}

	protected function loadFormData()
	{
		$app  = Factory::getApplication();
		$data = $app instanceof CMSWebApplicationInterface ? $app->getUserState('com_dpcalendar.edit.coupon.data', []) : [];
		if (empty($data)) {
			$data = $this->getItem();
		}

		$this->preprocessData('com_dpcalendar.coupon', $data);

		return $data instanceof BasicTable ? $data->getData() : $data;
	}

	protected function populateState()
	{
		$app = Factory::getApplication();

		$pk = $app->getInput()->getInt('co_id', 0);
		$this->setState('coupon.id', $pk);
		$this->setState('form.id', $pk);

		$return = $app->getInput()->get('return', '', 'base64');
		if (!Uri::isInternal(base64_decode((string)$return))) {
			$return = '';
		}

		$this->setState('return_page', base64_decode((string)$return));

		$this->setState('params', $app instanceof SiteApplication ? $app->getParams() : ComponentHelper::getParams('com_dpcalendar'));
	}

	public function getReturnPage(): string
	{
		return base64_encode((string)($this->getState('return_page', '') ?: Uri::base(true)));
	}
}
