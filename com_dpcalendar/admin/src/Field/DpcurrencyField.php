<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2024 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Component\DPCalendar\Administrator\Field;

\defined('_JEXEC') or die();

use Joomla\CMS\Factory;
use Joomla\CMS\Form\Field\ListField;

class DPCurrencyField extends ListField
{
	public $type = 'Dpcurrency';

	protected function getOptions(): array
	{
		$model = Factory::getApplication()->bootComponent('dpcalendar')->getMVCFactory()
		->createModel('Currency', 'Administrator');

		$currencies = [];

		if ((string)$this->element['mode'] === 'all') {
			foreach ($model->getExchangeRates() as $name => $rate) {
				$currencies[] = (object)['value' => $name, 'text' => $name];
			}
		} else {
			foreach ($model->getCurrencies() as $currency) {
				$currencies[] = (object)['value' => $currency->currency, 'text' => $currency->currency . ' ' . $currency->symbol];
			}
		}

		return array_merge(parent::getOptions(), $currencies);
	}
}
