<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2014 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DPCalendar\Plugin;

defined('_JEXEC') or die();

use DigitalPeak\ThinHTTP as HTTP;
use DPCalendar\Helper\DPCalendarHelper;
use DPCalendar\Helper\LayoutHelper;
use DPCalendar\HTML\Document\HtmlDocument;
use DPCalendar\Translator\Translator;
use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Layout\LayoutHelper as LayoutLayoutHelper;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Uri\Uri;

BaseDatabaseModel::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_dpcalendar/models');

/**
 * Base plugin for all payment gateway plugins of DPCalendar.
 */
abstract class PaymentPlugin extends CMSPlugin
{
	public $params;
	public $_type;
	public $_name;
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
	protected $app;

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
	 * The function can be used from subclasses to deliver an invoice. The array must contain two keys:
	 * - content: The binary content of the invoice
	 * - name: The name of the invoice
	 *
	 * @param \stdClass $booking
	 * @param \stdClass $paymentProvider
	 *
	 * @return array
	 */
	protected function getInvoiceData($booking, $paymentProvider): array
	{
		return [];
	}

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
		return LayoutLayoutHelper::render(
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
		$rootURL    = rtrim(Uri::base(), '/');
		$subpathURL = Uri::base(true);
		if (!empty($subpathURL) && ($subpathURL != '/')) {
			$rootURL = substr($rootURL, 0, -1 * strlen($subpathURL));
		}

		$tmpl = '';
		if ($t = $this->app->input->get('tmpl')) {
			$tmpl = '&tmpl=' . $t;
		}
		if ($t = $this->app->input->get('token')) {
			$tmpl = '&token=' . $t;
		}
		if ($t = $this->app->input->getInt('Itemid', 0)) {
			$tmpl .= '&Itemid=' . $t;
		}

		// The payment parameters
		$purchaseParameters             = [];
		$purchaseParameters['amount']   = $booking->price;
		$purchaseParameters['currency'] = strtoupper(DPCalendarHelper::getComponentParameter('currency', 'USD'));

		// The urls for call back actions
		$purchaseParameters['returnUrl'] = Route::_('index.php?option=com_dpcalendar&task=booking.pay&b_id=' . $booking->id . $tmpl, false);
		$purchaseParameters['returnUrl'] = $rootURL . $purchaseParameters['returnUrl'];
		$purchaseParameters['cancelUrl'] = Route::_('index.php?option=com_dpcalendar&task=booking.paycancel&b_id=' . $booking->id . $tmpl, false);
		$purchaseParameters['cancelUrl'] = $rootURL . $purchaseParameters['cancelUrl'];

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
	 * The function is called to generate the invoice.
	 *
	 * @param stdclass $booking
	 *
	 * @return array
	 */
	public function onDPCalendarLoadInvoice($booking)
	{
		$provider = null;
		foreach ($this->onDPPaymentProviders() as $p) {
			if ($p->id == $booking->processor) {
				$provider = $p;
			}
		}

		if (!$provider) {
			return;
		}

		return $this->getInvoiceData($booking, $provider);
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
		} catch (\Exception $exception) {
			$this->app->enqueueMessage(ucfirst($this->_name) . ': ' . $exception->getMessage(), 'error');
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
		Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_dpcalendar/tables');
		$booking = Table::getInstance('Booking', 'DPCalendarTable');

		// Load the booking so we can update only the values from the data array
		$booking->load($data['id']);

		// Merge the old data with the new one
		$data = array_merge($booking ? $booking->getProperties() : [], $data);

		// Save the data and make sure no valid event is triggered
		BaseDatabaseModel::getInstance('Booking', 'DPCalendarModel', ['event_after_save' => 'dontusethisevent'])->save($data);

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
		foreach ($this->params->get('providers') as $p) {
			if (isset($p->state) && $p->state == '0') {
				continue;
			}

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
			$data['b_id'] = $this->app->input->getInt('b_id', 0);
		}

		// Display the message and set it as failure
		if (!$msg) {
			$this->app->enqueueMessage($msg, 'error');
		}

		// Redirect to pay.cancel task
		$this->app->redirect(Route::_('index.php?option=com_dpcalendar&task=booking.paycancel&b_id=' . $data['b_id'], false));
	}

	/**
	 * @return HTTP
	 */
	protected function getHTTP()
	{
		return new HTTP();
	}
}
