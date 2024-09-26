<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2024 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Component\DPCalendar\Administrator\Model;

\defined('_JEXEC') or die();

use DigitalPeak\Component\DPCalendar\Administrator\Helper\DPCalendarHelper;
use DigitalPeak\ThinHTTP\CurlClient;
use Joomla\CMS\Application\CMSWebApplicationInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

class CurrencyModel extends BaseDatabaseModel
{
	/**
	 * Returns the actual currency.
	 */
	public function getActualCurrency(): \stdClass
	{
		$currencies = $this->getCurrencies();

		$code = null;
		$app  = Factory::getApplication();
		if ($app instanceof CMSWebApplicationInterface) {
			$code = $app->getSession()->get('com_dpcalendar.user.currency') ;
		}

		foreach ($currencies as $currency) {
			if ($currency->currency === $code) {
				return $currency;
			}
		}

		return reset($currencies);
	}

	/**
	 * Returns the list of currencies.
	 */
	public function getCurrencies(): array
	{
		$currencies = (array)DPCalendarHelper::getComponentParameter('bookingsys_currencies');
		if ($currencies === []) {
			return ['bookingsys_currencies0' => (object)[
				'currency'            => 'EUR',
				'symbol'              => '€',
				'separator'           => '.',
				'thousands_separator' => "'"
			]];
		}

		return $currencies;
	}

	/**
	 * The exchange rates list for today or default if API can't be called.
	 */
	public function getExchangeRates(): array
	{
		// Setup the file path
		$file = JPATH_CACHE . '/com_dpcalendar-exchange-rates/' . DPCalendarHelper::getDate()->format('Y-m-d') . '.json';

		// When the file exists, use it
		if (file_exists($file)) {
			return (array)json_decode(file_get_contents($file) ?: '{}');
		}

		// Ensure the directories do exist
		if (!is_dir(\dirname($file))) {
			mkdir(\dirname($file));
		}

		// The API key
		$key = DPCalendarHelper::getComponentParameter('bookingsys_exchangeratesapi_api_key');
		if ($key) {
			// Fetch the content
			$response = (new CurlClient())->get('https://api.exchangeratesapi.io/v1/latest?access_key=' . $key);

			// When rates field exists write the file
			if (!empty($response->rates)) {
				file_put_contents($file, json_encode($response->rates));
			}
		}

		// Use the default one when no file is found
		if (!file_exists($file)) {
			copy(JPATH_ADMINISTRATOR . '/components/com_dpcalendar/config/default-exchange-rates.json', $file);
		}

		// Return the rates
		return (array)json_decode(file_get_contents($file) ?: '{}');
	}

	/**
	 * Returns the prices for the currency from the session. If none do exist an automatic conversion is done.
	 */
	public function setupCurrencyPrices(\stdClass $event): void
	{
		// Get the defined currencies
		$currency = $this->getActualCurrency();

		$event->price           = $this->convert($event->price, $currency->currency);
		$event->booking_options = $this->convert($event->booking_options, $currency->currency);
	}

	private function convert(?\stdClass $prices, string $actualCurrency): ?\stdClass
	{
		// Ignore empty prices
		if (!$prices instanceof \stdClass || get_object_vars($prices) === []) {
			return null;
		}

		// The filtered prices
		$filteredPrices = new \stdClass();
		foreach ((array)$prices as $index => $price) {
			// If it matches the current currency, use it
			if ($price->currency === $actualCurrency) {
				$filteredPrices->$index = $price;
			}
		}

		// When not empty, then we assume event is properly set up
		if (get_object_vars($filteredPrices) !== []) {
			return $filteredPrices;
		}

		// The current exchange rates
		$currencies = $this->getExchangeRates();

		$fee = (float)DPCalendarHelper::getComponentParameter('bookingsys_exchange_fee', 0);

		// Loop over the prices
		foreach ((array)$prices as $index => $price) {
			$newPrice           = clone $price;
			$newPrice->currency = $actualCurrency;

			$newPrice->value = ($price->value / $currencies[$price->currency]) * $currencies[$actualCurrency];
			$newPrice->value = $fee !== 0.0 ? $newPrice->value + (($newPrice->value / 100) * $fee) : $newPrice->value;
			$newPrice->value = $newPrice->value > 10 ? ceil($newPrice->value) : $newPrice->value;

			// Add a converted price
			$filteredPrices->$index = $newPrice;
		}

		// Return the prices
		return $filteredPrices;
	}
}
