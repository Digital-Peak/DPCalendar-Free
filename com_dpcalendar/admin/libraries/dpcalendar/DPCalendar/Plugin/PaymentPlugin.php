<?php
/**
 * @package   DPCalendar
 * @author    Digital Peak http://www.digital-peak.com
 * @copyright Copyright (C) 2007 - 2019 Digital Peak. All rights reserved.
 * @license   http://www.gnu.org/licenses/gpl.html GNU/GPL
 */
namespace DPCalendar\Plugin;

use DPCalendar\Helper\LayoutHelper;
use DPCalendar\HTML\Document\HtmlDocument;
use DPCalendar\Translator\Translator;
use Omnipay\Common\AbstractGateway;
use Omnipay\Common\Message\ResponseInterface;
use Omnipay\Mollie\Message\Response\AbstractMollieResponse;

defined('_JEXEC') or die();

\JLoader::import('joomla.plugin.plugin');
\JModelLegacy::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_dpcalendar/models');

\JLoader::import('components.com_dpcalendar.vendor.autoload', JPATH_ADMINISTRATOR);

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
	 * Returns the Omnipay gateway of the current payment provider.
	 *
	 * @return AbstractGateway
	 */
	abstract protected function getPaymentGateway();

	/**
	 * Returns an array of fields to update the booking from the payment gateway.
	 *
	 * @param AbstractGateway   $gateway
	 * @param ResponseInterface $response
	 * @param \stdClass         $booking
	 */
	abstract protected function getPaymentData(AbstractGateway $gateway, ResponseInterface $response, $booking);

	/**
	 * The parameters for the purchase.
	 *
	 * @param AbstractGateway $gateway
	 * @param \stdClass       $booking
	 *
	 * @return array
	 */
	protected function getPurchaseParameters(AbstractGateway $gateway, $booking)
	{
		// Compile the root url for the callback of the payment gateway
		$rootURL    = rtrim(\JURI::base(), '/');
		$subpathURL = \JURI::base(true);
		if (!empty($subpathURL) && ($subpathURL != '/')) {
			$rootURL = substr($rootURL, 0, -1 * strlen($subpathURL));
		}

		$tmpl = '';
		if ($t = \JFactory::getApplication()->input->get('tmpl')) {
			$tmpl = '&tmpl=' . $t;
		}
		if ($t = \JFactory::getApplication()->input->getInt('Itemid')) {
			$tmpl .= '&Itemid=' . $t;
		}

		// The payment parameters
		$purchaseParameters             = [];
		$purchaseParameters['amount']   = $booking->price;
		$purchaseParameters['currency'] = strtoupper(\DPCalendarHelper::getComponentParameter('currency', 'USD'));

		// The urls for call back actions
		$purchaseParameters['returnUrl'] = $rootURL . \JRoute::_(
			'index.php?option=com_dpcalendar&task=booking.pay&b_id=' . $booking->id . '&paymentmethod=' . $this->_name . $tmpl,
			false
		);
		$purchaseParameters['cancelUrl'] = $rootURL . \JRoute::_(
			'index.php?option=com_dpcalendar&task=booking.paycancel&b_id=' . $booking->id . '&ptype=' . $this->_name . $tmpl,
			false
		);

		return $purchaseParameters;
	}

	/**
	 * The function is called when the start page of the payment gateway should be displayed.
	 * Some setup stuff can be done here.
	 *
	 * @param string    $paymentmethod
	 * @param \stdClass $booking
	 *
	 * @return bool|string
	 */
	public function onDPPaymentNew($paymentmethod, $booking)
	{
		// Check if this plugin should handle the request
		if ($paymentmethod != $this->_name && $paymentmethod != '0') {
			return false;
		}

		// Setup
		$gateway            = $this->getPaymentGateway();
		$purchaseParameters = $this->getPurchaseParameters($gateway, $booking);

		// Render the form of the plugin
		$layout = \JLayoutHelper::render(
			'purchase.form',
			[
				'booking'      => $booking,
				'params'       => $this->params,
				'returnUrl'    => $purchaseParameters['returnUrl'],
				'cancelUrl'    => $purchaseParameters['cancelUrl'],
				'translator'   => new Translator(),
				'layoutHelper' => new LayoutHelper(),
				'document'     => new HtmlDocument()
			],
			JPATH_PLUGINS . '/' . $this->_type . '/' . $this->_name . '/layouts'
		);

		// When a layout exists, then just display it
		if ($layout) {
			return $layout;
		}

		// No layout, so the payment gateway is doing it's work
		$response = $this->getNewResponse($gateway, $purchaseParameters, $booking);

		// Mostly we redirect here, if there is an error cancel the payment
		if ($response->isRedirect()) {
			$response->redirect();
		} else if (!$response->isSuccessful()) {
			$this->cancelPayment(['b_id' => $booking->id], $response->getMessage() ?: 'Server error!');

			return false;
		}

		// For safety redirect, but we should never land here
		\JFactory::getApplication()->redirect($purchaseParameters['returnUrl']);

		return true;
	}

	/**
	 * @param string $bookingmethod
	 * @param array  $data
	 *
	 * @return bool
	 */
	public function onDPPaymentCallBack($bookingmethod, $data)
	{
		// Check if this plugin should handle the request
		if ($bookingmethod != $this->_name) {
			return false;
		}

		// Get the objects to work on
		$booking = \JModelLegacy::getInstance('Booking', 'DPCalendarModel')->getItem($data['b_id']);
		$gateway = $this->getPaymentGateway();

		// get the response from the callback
		$response = $this->getCallBackResponse($gateway, $data, $booking);

		// Redirect if needed or if it is failed, then cancel the payment
		if ($response->isRedirect()) {
			$response->redirect();
		} else if (!$response->isSuccessful()) {
			// Mollie makes the stupid thing to provide all data as message
			$this->cancelPayment($data, $response instanceof AbstractMollieResponse ? '' : $response->getMessage());

			return false;
		}

		// Collect the payment data
		$data = $this->getPaymentData($gateway, $response, $booking);

		// If it is a string, then we have an error message and need to cancel the booking
		if (is_string($data)) {
			$this->cancelPayment([], $data);

			return false;
		}

		// Set the id of the booking
		$data['id'] = $booking->id;

		// Get the booking table
		\JTable::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_dpcalendar/tables');
		$booking = \JTable::getInstance('Booking', 'DPCalendarTable');

		// Load the booking so we can update only the values from the data array
		$booking->load($data['id']);

		// Set the current plugin as processor
		$data['processor'] = $this->_name;

		// Merge the old data with the new one
		$data = array_merge($booking ? (array)$booking : [], $data);

		// Remove some invalid variables
		$data = json_decode(str_replace('\u0000*\u0000_', '', json_encode($data)), true);
		unset($data['errors']);

		// Save the data and make sure no valid event is triggered
		\JModelLegacy::getInstance('Booking', 'DPCalendarModel', ['event_after_save' => 'dontusethisevent'])
			->save($data, false, $this->sendNotificationsOnCallback);

		return true;
	}

	/**
	 * Get the statement of the plugin which will be used after the booking process is finished.
	 *
	 * @param \stdClass $booking
	 *
	 * @return \stdClass|void
	 */
	public function onDPPaymentStatement($booking)
	{
		// Check if this plugin should handle the request
		if ($booking == null || $booking->processor != $this->_name) {
			return;
		}

		// Check if there is a language string we want
		$key = strip_tags('PLG_DPCALENDARPAY_' . strtoupper($this->_name) . '_PAYMENT_STATEMENT_TEXT');
		if (!\JFactory::getLanguage()->hasKey($key)) {
			return;
		}

		// Compile the statement object
		$return            = new \stdClass();
		$return->status    = true;
		$return->type      = $this->_name;
		$return->statement = \DPCalendar\Helper\DPCalendarHelper::getStringFromParams('payment_statement', $key, $this->params);

		return $return;
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
		// The app to work on
		$app = \JFactory::getApplication();

		// Make sure we have a booking to cancel
		if (!isset($data['b_id'])) {
			$data['b_id'] = $app->input->getInt('b_id');
		}

		// Display the message and set it as failure
		if (!is_null($msg)) {
			$data['dpcalendar_failure_reason'] = $msg;
			$app->enqueueMessage($msg, 'error');
		}

		// Log data in a file
		$this->log($data, true);

		// Redirect to pay.cancel task
		$app->redirect(\JRoute::_('index.php?option=com_dpcalendar&task=booking.paycancel&b_id=' . $data['b_id'] . '&ptype=' . $this->_name, false));
	}

	/**
	 * Create the callback response which is responsible to get some transaction details.
	 * Basically the purchase should be finished.
	 *
	 * @param AbstractGateway $gateway
	 * @param array           $data
	 * @param \stdClass       $booking
	 *
	 * @return ResponseInterface|null
	 */
	protected function getCallBackResponse(AbstractGateway $gateway, $data, $booking)
	{
		// If there is a complete purchase function, then use it
		if (method_exists($gateway, 'completePurchase')) {
			return $gateway->completePurchase($this->getPurchaseParameters($gateway, $booking))->send();
		}

		// If there is a purchase function, then use it
		if (method_exists($gateway, 'purchase')) {
			return $gateway->purchase($this->getPurchaseParameters($gateway, $booking))->send();
		}

		// If we land here then authorize again, but we actually never should
		return $gateway->authorize($this->getPurchaseParameters($gateway, $booking))->send();
	}

	/**
	 * Get a response for a new booking. Often this is the case where a redirect is performed.
	 *
	 * @param AbstractGateway $gateway
	 * @param array           $purchaseParameters
	 * @param \stdClass       $booking
	 *
	 * @return ResponseInterface|null
	 */
	protected function getNewResponse(AbstractGateway $gateway, $purchaseParameters, $booking)
	{
		// If there is a complete purchase function, then use it
		if (method_exists($gateway, 'purchase')) {
			return $gateway->purchase($purchaseParameters)->send();
		}

		// Authorize against the payment gateway
		return $gateway->authorize($purchaseParameters)->send();
	}

	/**
	 * Log functionality.
	 *
	 * @param array   $data
	 * @param boolean $isValid
	 */
	protected function log($data, $isValid)
	{
		$logFilenameBase = \JFactory::getApplication()->get('log_path') . '/plg_dpcalendarpay_' . strtolower($this->_name);

		$logFile = $logFilenameBase . '.php';
		\JLoader::import('joomla.filesystem.file');
		if (!\JFile::exists($logFile)) {
			$dummy = "<?php die(); ?>\n";
			\JFile::write($logFile, $dummy);
		} else {
			if (@filesize($logFile) > 1048756) {
				$altLog = $logFilenameBase . '-1.php';
				if (\JFile::exists($altLog)) {
					\JFile::delete($altLog);
				}
				\JFile::copy($logFile, $altLog);
				\JFile::delete($logFile);
				$dummy = "<?php die(); ?>\n";
				\JFile::write($logFile, $dummy);
			}
		}
		$logData = file_get_contents($logFile);
		if ($logData === false) {
			$logData = '';
		}
		$logData    .= "\n" . str_repeat('-', 80);
		$pluginName = strtoupper($this->_name);
		$logData    .= $isValid ? 'VALID ' . $pluginName . ' IPN' : 'INVALID ' . $pluginName . ' IPN *** FRAUD ATTEMPT OR INVALID NOTIFICATION ***';
		$logData    .= "\nDate/time : " . gmdate('Y-m-d H:i:s') . " GMT\n\n";
		foreach ($data as $key => $value) {
			$logData .= '  ' . str_pad($key, 30, ' ') . print_r($value, true) . "\n";
		}
		$logData .= "\n";
		\JFile::write($logFile, $logData);
	}
}
