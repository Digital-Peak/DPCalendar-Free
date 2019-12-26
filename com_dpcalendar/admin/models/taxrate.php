<?php
/**
 * @package   DPCalendar
 * @author    Digital Peak http://www.digital-peak.com
 * @copyright Copyright (C) 2007 - 2019 Digital Peak. All rights reserved.
 * @license   http://www.gnu.org/licenses/gpl.html GNU/GPL
 */
defined('_JEXEC') or die();

class DPCalendarModelTaxrate extends JModelAdmin
{
	protected $text_prefix = 'COM_DPCALENDAR_TAXRATE';

	protected function canDelete($record)
	{
		if (!empty($record->id)) {
			if ($record->state != -2) {
				return;
			}

			return parent::canDelete($record);
		}
	}

	public function getItemByCountry($countryId)
	{
		$query = $this->_db->getQuery(true)
			->select('*')
			->from('#__dpcalendar_taxrates')->where('countries like\'%"country":"' . $countryId . '"%\'')->where('state = 1');
		$this->_db->setQuery($query);

		$taxRate = $this->_db->loadObject();
		if (!$taxRate || !$taxRate->id) {
			return null;
		}

		$taxRate->rate = sprintf('%g', $taxRate->rate);

		return $taxRate;
	}

	public function getItem($pk = null)
	{
		$item = parent::getItem($pk);

		if ($item->countries) {
			$item->countries = json_decode($item->countries);
		}

		$item->rate = sprintf('%g', $item->rate);

		return $item;
	}

	public function getTable($type = 'Taxrate', $prefix = 'DPCalendarTable', $config = [])
	{
		return JTable::getInstance($type, $prefix, $config);
	}

	public function getForm($data = [], $loadData = true, $controlName = 'jform')
	{
		// Get the form.
		$form = $this->loadForm('com_dpcalendar.taxrate', 'taxrate', ['control' => $controlName, 'load_data' => $loadData]);
		if (empty($form)) {
			return false;
		}

		// Modify the form based on access controls.
		if (!$this->canEditState((object)$data)) {
			// Disable fields for display.
			$form->setFieldAttribute('ordering', 'disabled', 'true');
			$form->setFieldAttribute('state', 'disabled', 'true');
			$form->setFieldAttribute('publish_up', 'disabled', 'true');
			$form->setFieldAttribute('publish_down', 'disabled', 'true');

			// Disable fields while saving.
			$form->setFieldAttribute('ordering', 'filter', 'unset');
			$form->setFieldAttribute('state', 'filter', 'unset');
			$form->setFieldAttribute('publish_up', 'filter', 'unset');
			$form->setFieldAttribute('publish_down', 'filter', 'unset');
		}

		return $form;
	}

	protected function loadFormData()
	{
		$data = JFactory::getApplication()->getUserState('com_dpcalendar.edit.taxrate.data', []);

		if (empty($data)) {
			$data = $this->getItem();
		}

		$this->preprocessData('com_dpcalendar.taxrate', $data);

		return $data;
	}

	protected function populateState()
	{
		$app = JFactory::getApplication();

		$pk = $app->input->getInt('r_id');
		$this->setState('taxrate.id', $pk);
		$this->setState('form.id', $pk);

		$return = $app->input->getVar('return', null, 'default', 'base64');

		if (!JUri::isInternal(base64_decode($return))) {
			$return = null;
		}

		$this->setState('return_page', base64_decode($return));

		$params = JComponentHelper::getParams('com_dpcalendar');
		$this->setState('params', $params);
	}

	public function getReturnPage()
	{
		return base64_encode($this->getState('return_page'));
	}
}
