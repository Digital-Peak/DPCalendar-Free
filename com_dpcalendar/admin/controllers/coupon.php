<?php

use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2020 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */
defined('_JEXEC') or die();

class DPCalendarControllerCoupon extends FormController
{
	public $input;
	public $id;
	public $error;
	protected $text_prefix = 'COM_DPCALENDAR_COUPON';

	public function save($key = null, $urlVar = 'co_id')
	{
		$data = $this->input->post->get('jform', [], 'array');

		if (!isset($data['calendars'])) {
			$data['calendars'] = [];
		}
		if (!isset($data['users'])) {
			$data['users'] = [];
		}

		$this->input->post->set('jform', $data);

		return parent::save($key, $urlVar);
	}

	protected function postSaveHook(BaseDatabaseModel $model, $validData = [])
	{
		$this->id    = $model->getState('coupon.id');
		$this->error = $model->getError();
	}

	public function edit($key = null, $urlVar = 'co_id')
	{
		return parent::edit($key, $urlVar);
	}

	public function cancel($key = 'co_id')
	{
		return parent::cancel($key);
	}
}
