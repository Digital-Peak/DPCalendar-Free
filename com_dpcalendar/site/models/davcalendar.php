<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2014 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */
defined('_JEXEC') or die();

JLoader::import('joomla.application.component.modeladmin');
JTable::addIncludePath(JPATH_SITE . '/components/com_dpcalendar/tables');

class DPCalendarModelDavcalendar extends JModelAdmin
{
	public function getTable($type = 'Davcalendar', $prefix = 'DPCalendarTable', $config = [])
	{
		return JTable::getInstance($type, $prefix, $config);
	}

	public function getForm($data = [], $loadData = true)
	{
		$form = $this->loadForm('com_dpcalendar.davcalendar', 'davcalendar', ['control' => 'jform', 'load_data' => $loadData]);
		if (empty($form)) {
			return false;
		}

		return $form;
	}

	protected function loadFormData()
	{
		$data = JFactory::getApplication()->getUserState('com_dpcalendar.edit.davcalendar.data', []);

		if (empty($data)) {
			$data = $this->getItem();
		}

		return $data;
	}

	public function getReturnPage()
	{
		return base64_encode($this->getState('return_page'));
	}

	protected function populateState()
	{
		$app = JFactory::getApplication();

		$pk = $app->input->getInt('c_id');
		$this->setState('davcalendar.id', $pk);
		$this->setState('form.id', $pk);

		$return = $app->input->get('return', null, 'default', 'base64');
		if (!JUri::isInternal(base64_decode($return))) {
			$return = null;
		}

		$this->setState('return_page', base64_decode($return));

		$this->setState('params', method_exists($app, 'getParams') ? $app->getParams() : JComponentHelper::getParams('com_dpcalendar'));

		$this->setState('layout', $app->input->getCmd('layout'));
	}
}
