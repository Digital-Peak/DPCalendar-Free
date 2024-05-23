<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2014 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Component\DPCalendar\Site\Model;

defined('_JEXEC') or die();

use DigitalPeak\Component\DPCalendar\Administrator\Table\BasicTable;
use Joomla\CMS\Application\CMSWebApplicationInterface;
use Joomla\CMS\Application\SiteApplication;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\Uri\Uri;

class DavcalendarModel extends AdminModel
{
	public function getTable($type = 'Davcalendar', $prefix = 'Administrator', $config = [])
	{
		return parent::getTable($type, $prefix, $config);
	}

	public function getForm($data = [], $loadData = true)
	{
		return $this->loadForm('com_dpcalendar.davcalendar', 'davcalendar', ['control' => 'jform', 'load_data' => $loadData]);
	}

	protected function loadFormData()
	{
		$app  = Factory::getApplication();
		$data = null;
		if ($app instanceof CMSWebApplicationInterface) {
			$data = $app->getUserState('com_dpcalendar.edit.davcalendar.data', []);
		}

		if (empty($data)) {
			$data = $this->getItem();
		}

		return $data instanceof BasicTable ? $data->getData() : $data;
	}

	public function getReturnPage(): string
	{
		return base64_encode((string)$this->getState('return_page', ''));
	}

	protected function populateState()
	{
		$app = Factory::getApplication();

		$pk = $app->getInput()->getInt('c_id', 0);
		$this->setState('davcalendar.id', $pk);
		$this->setState('form.id', $pk);

		$return = $app->getInput()->get('return', null, 'base64');
		if (!Uri::isInternal(base64_decode((string)($return ?: '')))) {
			$return = null;
		}

		$this->setState('return_page', base64_decode((string)($return ?: '')));

		$this->setState('params', $app instanceof SiteApplication ? $app->getParams() : ComponentHelper::getParams('com_dpcalendar'));

		$this->setState('layout', $app->getInput()->getCmd('layout'));
	}
}
