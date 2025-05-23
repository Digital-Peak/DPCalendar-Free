<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2019 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Component\DPCalendar\Administrator\Model;

\defined('_JEXEC') or die();

use DigitalPeak\Component\DPCalendar\Administrator\Table\BasicTable;
use DigitalPeak\ThinHTTP\CurlClientFactory;
use Joomla\CMS\Application\CMSWebApplicationInterface;
use Joomla\CMS\Application\SiteApplication;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\Uri\Uri;

class TaxrateModel extends AdminModel
{
	protected $text_prefix = 'COM_DPCALENDAR_TAXRATE';

	public function euvatimport(): void
	{
		$data = (new CurlClientFactory())->create()->get('https://raw.githubusercontent.com/DavidAnderson684/euvatrates.com/refs/heads/master/rates.json');
		if (empty($data->rates)) {
			return;
		}

		foreach ($data->rates as $code => $rate) {
			$country = $this->bootComponent('dpcalendar')->getMVCFactory()
				->createModel('Country', 'Administrator', ['ignore_request' => true])->getItem(['short_code' => $code]);
			if (!$country instanceof \stdClass) {
				continue;
			}

			$item = $this->getItemByCountry($country->id);

			// If the rate exists, update it
			if (!$item instanceof \stdClass) {
				$item            = new \stdClass();
				$item->title     = $rate->country;
				$item->countries = ['countries0' => ['country' => $country->id]];
				$item->state     = 1;
			}

			$item->rate = $rate->standard_rate;

			$this->save((array)$item);
			$this->setState($this->getName() . '.id', null);
		}
	}

	public function getItemByCountry(string $countryId): ?\stdClass
	{
		$query = $this->getDatabase()->getQuery(true)
			->select('*')
			->from('#__dpcalendar_taxrates')
			->where('(countries like \'%{"country":"' . $countryId . '"}%\' or countries like \'%{"country":' . $countryId . "}%')")
			->where('state = 1');
		$this->getDatabase()->setQuery($query);

		$taxRate = $this->getDatabase()->loadObject();
		if (!$taxRate || !$taxRate->id) {
			return null;
		}

		$taxRate->rate = \sprintf('%g', $taxRate->rate);

		return $taxRate;
	}

	public function getItem($pk = null)
	{
		$item = parent::getItem($pk);
		if ($item === false) {
			return $item;
		}

		if ($item->countries) {
			$item->countries = json_decode((string)$item->countries);
		}

		$item->rate = \sprintf('%g', $item->rate);

		return $item;
	}

	public function getTable($type = 'Taxrate', $prefix = 'Administrator', $config = [])
	{
		return parent::getTable($type, $prefix, $config);
	}

	public function getForm($data = [], $loadData = true, string $controlName = 'jform')
	{
		$form = $this->loadForm('com_dpcalendar.taxrate', 'taxrate', ['control' => $controlName, 'load_data' => $loadData]);

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
		$data = $app instanceof CMSWebApplicationInterface ? $app->getUserState('com_dpcalendar.edit.taxrate.data', []) : [];
		if (empty($data)) {
			$data = $this->getItem();
		}

		$this->preprocessData('com_dpcalendar.taxrate', $data);

		return $data instanceof BasicTable ? $data->getData() : $data;
	}

	protected function populateState()
	{
		$app = Factory::getApplication();

		$pk = $app->getInput()->getInt('r_id', 0);
		$this->setState('taxrate.id', $pk);
		$this->setState('form.id', $pk);

		$return = $app->getInput()->get('return', '', 'base64');
		if (!Uri::isInternal(base64_decode((string)$return))) {
			$return = '';
		}

		$this->setState('return_page', base64_decode((string)$return));

		$this->setState('params', $app instanceof SiteApplication ? $app->getParams() : ComponentHelper::getParams('com_dpcalendar'));
	}

	protected function canDelete($record)
	{
		if (!empty($record->state) && $record->state != -2) {
			return false;
		}

		return parent::canDelete($record);
	}

	public function getReturnPage(): string
	{
		return base64_encode((string)($this->getState('return_page', '') ?: Uri::base(true)));
	}
}
