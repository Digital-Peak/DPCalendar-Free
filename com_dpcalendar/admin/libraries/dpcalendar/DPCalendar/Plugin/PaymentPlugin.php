<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2014 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */
namespace DPCalendar\Plugin;

defined('_JEXEC') or die();

use DPCalendar\Helper\HTTP;
use DPCalendar\Helper\LayoutHelper;
use DPCalendar\HTML\Document\HtmlDocument;
use DPCalendar\Translator\Translator;
use Joomla\CMS\Application\CMSApplication;

\JLoader::import('joomla.plugin.plugin');
\JModelLegacy::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_dpcalendar/models');

/**
 * Base plugin for all payment gateway plugins of DPCalendar.
 */
abstract class PaymentPlugin extends \JPlugin
{
	/**
	 * Should a notification being sent after the callback.
	 *
	 * @var bool
	 */
	protected $sendNotificationsOnCallback = true;

	/**
	 * Joomla will load the language files automatically.
	 *
	 * @var bool
	 */
	protected $autoloadLanguage = true;

	/**
	 * @var CMSApplication
	 */
	protected $app = null;

	/**
	 * Method to finish the transaction. Returns the data for the booking with transaction information.
	 *
	 * @param           $data
	 * @param           $booking
	 * @param \stdClass $paymentProvider
	 *
	 * @return array
	 */
	abstract protected function finishTransaction($data, $booking, $paymentProvider);

	/**
	 * The function is called to render the payment provider output.
	 * Some setup stuff can be done here or redirecting to a payment provider.
	 *
	 * @param \stdClass $booking
	 * @param \stdClass $paymentProvider
	 *
	 * @return string
	 */
	protected function startTransaction($booking, $paymentProvider)
	{
		$purchaseParameters = $this->getPurchaseParameters($booking);

		// Render the form of the plugin
		return \JLayoutHelper::render(
			'purchase.form',
			[
				'booking'         => $booking,
				'paymentProvider' => $paymentProvider,
				'params'          => $this->params,
				'returnUrl'       => $purchaseParameters['returnUrl'],
				'cancelUrl'       => $purchaseParameters['cancelUrl'],
				'translator'      => new Translator(),
				'layoutHelper'    => new LayoutHelper(),
				'document'        => new HtmlDocument()
			],
			JPATH_PLUGINS . '/' . $this->_type . '/' . $this->_name . '/layouts'
		);
	}

	/**
	 * The parameters for the purchase.
	 *
	 * @param \stdClass $booking
	 *
	 * @return array
	 */
	protected function getPurchaseParameters($booking)
	{
		// Compile the root url for the callback
		$rootURL    = rtrim(\JURI::base(), '/');
		$subpathURL = \JURI::base(true);
		if (!empty($subpathURL) && ($subpathURL != '/')) {
			$rootURL = substr($rootURL, 0, -1 * strlen($subpathURL));
		}

		$tmpl = '';
		if ($t = $this->app->input->get('tmpl')) {
			$tmpl = '&tmpl=' . $t;
		}
		if ($t = $this->app->input->getInt('Itemid')) {
			$tmpl .= '&Itemid=' . $t;
		}

		// The payment parameters
		$purchaseParameters             = [];
		$purchaseParameters['amount']   = $booking->price;
		$purchaseParameters['currency'] = strtoupper(\DPCalendarHelper::getComponentParameter('currency', 'USD'));

		// The urls for call back actions
		$purchaseParameters['returnUrl'] = $rootURL . \JRoute::_(
				'index.php?option=com_dpcalendar&task=booking.pay&b_id=' . $booking->id . $tmpl,
				false
			);
		$purchaseParameters['cancelUrl'] = $rootURL . \JRoute::_(
				'index.php?option=com_dpcalendar&task=booking.paycancel&b_id=' . $booking->id . $tmpl,
				false
			);

		return $purchaseParameters;
	}

	/**
	 * The function is called when the start page of the payment gateway should be displayed.
	 * Some setup stuff can be done here.
	 *
	 * @param \stdClass $booking
	 *
	 * @return bool|string
	 */
	public function onDPPaymentNew($booking)
	{
		$provider = null;
		foreach ($this->onDPPaymentProviders() as $p) {
			if ($p->id == $booking->processor) {
				$provider = $p;
			}
		}

		if (!$provider) {
			return false;
		}

		return $this->startTransaction($booking, $provider);
	}

	/**
	 * Callback function when a booking is processed by the payment provider.
	 *
	 * @param \stdClass $booking
	 * @param array     $data
	 *
	 * @return bool
	 */
	public function onDPPaymentCallBack($booking, $data)
	{
		try {
			$provider = null;
			foreach ($this->onDPPaymentProviders() as $p) {
				if ($p->id == $booking->processor) {
					$provider = $p;
				}
			}

			if (!$provider) {
				return false;
			}

			// Get the response from the callback
			$data = $this->finishTransaction($data, $booking, $provider);
		} catch (\Exception $e) {
			$this->app->enqueueMessage(ucfirst($this->_name) . ': ' . $e->getMessage(), 'error');
			$this->app->redirect(\DPCalendarHelperRoute::getBookingRoute($booking) . '&layout=confirm');

			return false;
		}

		if ($data['state'] == 6) {
			$this->cancelPayment([], '');

			return false;
		}

		// Set the id of the booking
		$data['id'] = $booking->id;

		// Get the booking table
		\JTable::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_dpcalendar/tables');
		$booking = \JTable::getInstance('Booking', 'DPCalendarTable');

		// Load the booking so we can update only the values from the data array
		$booking->load($data['id']);

		// Merge the old data with the new one
		$data = array_merge($booking ? $booking->getProperties() : [], $data);

		// Save the data and make sure no valid event is triggered
		\JModelLegacy::getInstance('Booking', 'DPCalendarModel', ['event_after_save' => 'dontusethisevent'])->save($data);

		return true;
	}

	/**
	 * Get the payment providers of the plugin.
	 *
	 * @param string $name
	 *
	 * @return \stdClass|void
	 */
	public function onDPPaymentProviders($name = null)
	{
		if ($name && $name != $this->_name || !$this->params->get('providers')) {
			return [];
		}

		$providers = [];
		foreach ($this->params->get('providers') as $index => $p) {
			$provider     = clone $p;
			$provider->id = $this->_name . '-' . $provider->id;

			$provider->plugin_name = $this->_name;
			$provider->plugin_type = $this->_type;

			if (empty($provider->icon)) {
				$provider->icon = 'media/plg_' . $provider->plugin_type . '_' . $provider->plugin_name . '/images/' . $provider->plugin_name . '.svg';
			}
			if (!empty($provider->icon) && strpos($provider->icon, '.svg') > 0) {
				$provider->icon = JPATH_ROOT . '/' . $provider->icon;
			}

			$providers[] = $provider;
		}

		return $providers;
	}

	/**
	 * Canceling the payment will delete the booking.
	 *
	 * @param array  $data
	 * @param string $msg
	 *
	 * @throws \Exception
	 */
	protected function cancelPayment($data, $msg = null)
	{
		// Make sure we have a booking to cancel
		if (!isset($data['b_id'])) {
			$data['b_id'] = $this->app->input->getInt('b_id');
		}

		// Display the message and set it as failure
		if (!$msg) {
			$this->app->enqueueMessage($msg, 'error');
		}

		// Redirect to pay.cancel task
		$this->app->redirect(\JRoute::_('index.php?option=com_dpcalendar&task=booking.paycancel&b_id=' . $data['b_id'], false));
	}

	/**
	 * @return HTTP
	 */
	protected function getHTTP()
	{
		return new HTTP();
	}
}
