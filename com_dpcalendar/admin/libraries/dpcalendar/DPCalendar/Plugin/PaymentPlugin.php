<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2018 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */
namespace DPCalendar\Plugin;

use Omnipay\Common\GatewayInterface;

defined('_JEXEC') or die();

\JLoader::import('joomla.plugin.plugin');
\JModelLegacy::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_dpcalendar/models');

\JLoader::import('components.com_dpcalendar.libraries.vendor.autoload', JPATH_ADMINISTRATOR);

/**
 * Base plugin for all payment gateway plugins of DPCalendar.
 */
abstract class PaymentPlugin extends \JPlugin
{
	protected $autoloadLanguage = true;

	/**
	 * Returns the Omnipay gateway.
	 *
	 * @return \Omnipay\Common\AbstractGateway
	 */
	abstract protected function getPaymentGateway();

	/**
	 * Returns the array of field to update the booking from the payment
	 * gateway.
	 *
	 * @param \Omnipay\Common\AbstractGateway $gateway
	 * @param array                           $transactionData
	 * @param \stdClass                       $booking
	 */
	abstract protected function getPaymentData($gateway, $transactionData, $booking);

	/**
	 * The parameters for the purchase.
	 *
	 * @param GatewayInterface $gateway
	 * @param \stdClass        $booking
	 *
	 * @return array
	 */
	protected function getPurchaseParameters($gateway, $booking)
	{
		$rootURL    = rtrim(\JURI::base(), '/');
		$subpathURL = \JURI::base(true);
		if (!empty($subpathURL) && ($subpathURL != '/')) {
			$rootURL = substr($rootURL, 0, -1 * strlen($subpathURL));
		}

		$tmpl = '';
		if ($t = \JFactory::getApplication()->input->get('tmpl')) {
			$tmpl = '&tmpl=' . $t;
		}

		$purchaseParameters              = array();
		$purchaseParameters['amount']    = $booking->price;
		$purchaseParameters['currency']  = strtoupper(\DPCalendarHelper::getComponentParameter('currency', 'USD'));
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

	public function onDPPaymentNew($paymentmethod, $booking)
	{
		if ($paymentmethod != $this->_name && $paymentmethod != '0') {
			return false;
		}

		$gateway = $this->getPaymentGateway();

		$purchaseParameters = $this->getPurchaseParameters($gateway, $booking);

		$layout = \JLayoutHelper::render(
			'purchase.form',
			array(
				'booking'   => $booking,
				'params'    => $this->params,
				'returnUrl' => $purchaseParameters['returnUrl'],
				'cancelUrl' => $purchaseParameters['cancelUrl']
			),
			JPATH_PLUGINS . '/' . $this->_type . '/' . $this->_name . '/layouts'
		);

		if ($layout) {
			return $layout;
		}

		$response = null;

		if (method_exists($gateway, 'purchase')) {
			$response = $gateway->purchase($purchaseParameters)->send();
		} else {
			$response = $gateway->authorize($purchaseParameters)->send();
		}

		if ($response->isRedirect()) {
			$response->redirect();
		} else if (!$response->isSuccessful()) {
			$this->cancelPayment(array('b_id' => $booking->id), $response->getMessage() ?: 'Server error!');

			return false;
		}

		\JFactory::getApplication()->redirect($purchaseParameters['returnUrl']);

		return true;
	}

	public function onDPPaymentCallBack($bookingmethod, $data)
	{
		// Check if we're supposed to handle this
		if ($bookingmethod != $this->_name) {
			return false;
		}

		$booking = \JModelLegacy::getInstance('Booking', 'DPCalendarModel')->getItem($data['b_id']);

		$gateway = $this->getPaymentGateway();

		$response = null;
		if (method_exists($gateway, 'completePurchase')) {
			$response = $gateway->completePurchase($this->getPurchaseParameters($gateway, $booking))
				->send();
		} else if (method_exists($gateway, 'purchase')) {
			$response = $gateway->purchase($this->getPurchaseParameters($gateway, $booking))
				->send();
		} else {
			$response = $gateway->authorize($this->getPurchaseParameters($gateway, $booking))
				->send();
		}

		// Error during checkout
		if (!$response->isSuccessful()) {
			$this->cancelPayment($response->getData(), $response->getMessage());

			return false;
		}

		$data = $this->getPaymentData($gateway, $response->getData(), $booking);
		if (is_string($data)) {
			$this->cancelPayment(array(), $data);

			return false;
		}

		$data['id'] = $booking->id;

		\JTable::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_dpcalendar/tables');
		$booking = \JTable::getInstance('Booking', 'DPCalendarTable');

		$booking->load($data['id']);

		$data['processor'] = $this->_name;

		if ($booking) {
			$dataOld = (array)$booking;
		} else {
			$dataOld = array();
		}
		$data = array_merge($dataOld, $data);

		// Remove some invalid variables
		$data = json_decode(str_replace('\u0000*\u0000_', '', json_encode($data)), true);
		unset($data['errors']);

		\JModelLegacy::getInstance('Booking', 'DPCalendarModel', array('event_after_save' => 'dontusethisevent'))->save($data);

		return true;
	}

	public function onDPPaymentStatement($booking)
	{
		if ($booking == null || $booking->processor != $this->_name) {
			return;
		}
		$return            = new \stdClass();
		$return->status    = true;
		$return->type      = $this->_name;
		$return->statement = \DPCalendar\Helper\DPCalendarHelper::getStringFromParams(
			'payment_statement',
			'PLG_DPCALENDARPAY_MANUAL_PAYMENT_STATEMENT_TEXT',
			$this->params
		);

		return $return;
	}

	protected function cancelPayment($data, $msg = null)
	{
		$app = \JFactory::getApplication();

		if (!isset($data['b_id'])) {
			$data['b_id'] = $app->input->getInt('b_id');
		}
		if (!is_null($msg)) {
			$data['dpcalendar_failure_reason'] = $msg;
			$app->enqueueMessage($msg, 'error');
		}
		// Log data in a file
		$this->log($data, true);

		// Redirect to pay.cancel task
		$app->redirect(\JRoute::_('index.php?option=com_dpcalendar&task=booking.paycancel&b_id=' . $data['b_id'] . '&ptype=' . $this->_name, false));
	}

	protected function log($data, $isValid)
	{
		$config = \JFactory::getConfig();
		if (version_compare(JVERSION, '3.0', 'ge')) {
			$logpath = $config->get('log_path');
		} else {
			$logpath = $config->getValue('log_path');
		}

		$logFilenameBase = $logpath . '/plg_dpcalendarpay_' . strtolower($this->_name);

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
			$logData .= '  ' . str_pad($key, 30, ' ') . $value . "\n";
		}
		$logData .= "\n";
		\JFile::write($logFile, $logData);
	}
}
