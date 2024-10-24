<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2019 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Component\DPCalendar\Administrator\Model;

\defined('_JEXEC') or die();

use DigitalPeak\Component\DPCalendar\Administrator\Table\BasicTable;
use Joomla\CMS\Application\CMSWebApplicationInterface;
use Joomla\CMS\Application\SiteApplication;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\Uri\Uri;

class CountryModel extends AdminModel
{
	protected $text_prefix = 'COM_DPCALENDAR_COUNTRY';

	protected function canDelete($record)
	{
		if (!empty($record->state) && $record->state != -2) {
			return false;
		}

		return parent::canDelete($record);
	}

	public function getTable($type = 'Country', $prefix = 'Administrator', $config = [])
	{
		return parent::getTable($type, $prefix, $config);
	}

	public function getForm($data = [], $loadData = true, string $controlName = 'jform')
	{
		// Get the form.
		$form = $this->loadForm('com_dpcalendar.country', 'country', ['control' => $controlName, 'load_data' => $loadData]);

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
		$app  = Factory::getApplication();
		$data = $app instanceof CMSWebApplicationInterface ? $app->getUserState('com_dpcalendar.edit.country.data', []) : [];
		if (empty($data)) {
			$data = $this->getItem();
		}

		$this->preprocessData('com_dpcalendar.country', $data);

		return $data instanceof BasicTable ? $data->getData() : $data;
	}

	protected function populateState()
	{
		$app = Factory::getApplication();

		$pk = $app->getInput()->getInt('c_id', 0);
		$this->setState('country.id', $pk);
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
