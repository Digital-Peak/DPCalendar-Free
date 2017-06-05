<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2017 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */
defined('_JEXEC') or die();

JLoader::import('joomla.application.component.modeladmin');
JTable::addIncludePath(JPATH_SITE . DS . 'components' . DS . 'com_dpcalendar' . DS . 'tables');

class DPCalendarModelDavcalendar extends JModelAdmin
{

	public function getTable ($type = 'Davcalendar', $prefix = 'DPCalendarTable', $config = array())
	{
		return JTable::getInstance($type, $prefix, $config);
	}

	public function getForm ($data = array(), $loadData = true)
	{
		$app = JFactory::getApplication();

		$form = $this->loadForm('com_dpcalendar.davcalendar', 'davcalendar', array(
				'control' => 'jform',
				'load_data' => $loadData
		));
		if (empty($form))
		{
			return false;
		}

		return $form;
	}

	protected function loadFormData ()
	{
		$data = JFactory::getApplication()->getUserState('com_dpcalendar.edit.davcalendar.data', array());

		if (empty($data))
		{
			$data = $this->getItem();
		}

		return $data;
	}

	public function getReturnPage ()
	{
		return base64_encode($this->getState('return_page'));
	}

	protected function populateState ()
	{
		$app = JFactory::getApplication();

		$pk = JFactory::getApplication()->input->getVar('c_id');
		$this->setState('davcalendar.id', $pk);
		$this->setState('form.id', $pk);

		$return = JFactory::getApplication()->input->getVar('return', null, 'default', 'base64');

		if (! JUri::isInternal(base64_decode($return)))
		{
			$return = null;
		}

		$this->setState('return_page', base64_decode($return));

		$params = $app->getParams();
		$this->setState('params', $params);

		$this->setState('layout', JFactory::getApplication()->input->getCmd('layout'));
	}
}
