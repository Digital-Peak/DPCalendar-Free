<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2014 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Component\DPCalendar\Administrator\Plugin;

defined('_JEXEC') or die();

use DigitalPeak\Component\DPCalendar\Administrator\Helper\DPCalendarHelper;
use DigitalPeak\Component\DPCalendar\Administrator\HTML\Document\HtmlDocument;
use DigitalPeak\Component\DPCalendar\Administrator\Translator\Translator;
use DigitalPeak\Component\DPCalendar\Site\Helper\RouteHelper;
use DigitalPeak\ThinHTTP\ClientFactoryAwareInterface;
use DigitalPeak\ThinHTTP\ClientFactoryAwareTrait;
use Joomla\CMS\Application\CMSApplicationInterface;
use Joomla\CMS\Application\CMSWebApplicationInterface;
use Joomla\CMS\Layout\LayoutHelper as LayoutLayoutHelper;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Joomla\Event\DispatcherInterface;

/**
 * Base plugin for all payment gateway plugins of DPCalendar.
 */
abstract class PaymentPlugin extends CMSPlugin implements ClientFactoryAwareInterface
{
	use ClientFactoryAwareTrait;

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

	public function __construct(DispatcherInterface $dispatcher, array $config = [])
	{
		parent::__construct($dispatcher, $config);
	}

	/**
	 * Method to finish the transaction. Returns the data for the booking with transaction information.
	 */
	abstract protected function finishTransaction(array $data, \stdClass $booking, \stdClass $paymentProvider): array;

	/**
	 * The function can be used from subclasses to deliver an invoice. The array must contain two keys:
	 * - content: The binary content of the invoice
	 * - name: The name of the invoice
	 */
	protected function getInvoiceData(\stdClass $booking, \stdClass $paymentProvider): array
	{
		return [];
	}

	/**
	 * The function is called to render the payment provider output.
	 * Some setup stuff can be done here or redirecting to a payment provider.
	 */
	protected function startTransaction(\stdClass $booking, \stdClass $paymentProvider): string
	{
		$app = $this->getApplication();
		if (!$app instanceof CMSApplicationInterface) {
			return '';
		}

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
				'layoutHelper'    => $app->bootComponent('dpcalendar')->getMVCFactory()->createModel('Layout', 'Administrator'),
				'document'        => new HtmlDocument()
			],
			JPATH_PLUGINS . '/' . $this->_type . '/' . $this->_name . '/layouts'
		);
	}

	/**
	 * The parameters for the purchase.
	 */
	protected function getPurchaseParameters(\stdClass $booking): array
	{
		$app = $this->getApplication();
		if (!$app instanceof CMSWebApplicationInterface) {
			return [];
		}

		// Compile the root url for the callback
		$rootURL    = rtrim(Uri::base(), '/');
		$subpathURL = Uri::base(true);
		if (!empty($subpathURL) && ($subpathURL != '/')) {
			$rootURL = substr($rootURL, 0, -1 * strlen($subpathURL));
		}

		$tmpl = '';
		if ($t = $app->getInput()->get('tmpl')) {
			$tmpl = '&tmpl=' . $t;
		}
		if ($t = $app->getInput()->get('token')) {
			$tmpl = '&token=' . $t;
		}
		if ($t = $app->getInput()->getInt('Itemid', 0)) {
			$tmpl .= '&Itemid=' . $t;
		}

		// The payment parameters
		$purchaseParameters             = [];
		$purchaseParameters['amount']   = $booking->price;
		$purchaseParameters['currency'] = strtoupper((string)DPCalendarHelper::getComponentParameter('currency', 'USD'));

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
	 */
	public function onDPPaymentNew(\stdClass $booking): false|string
	{
		$provider = array_filter($this->onDPPaymentProviders(), static fn ($p): bool => $p->id == $booking->processor);
		if ($provider === []) {
			return false;
		}

		return $this->startTransaction($booking, reset($provider));
	}

	/**
	 * The function is called to generate the invoice.
	 */
	public function onDPCalendarLoadInvoice(\stdClass $booking): ?array
	{
		$provider = array_filter($this->onDPPaymentProviders(), static fn ($p): bool => $p->id == $booking->processor);
		if ($provider === []) {
			return null;
		}

		return $this->getInvoiceData($booking, reset($provider));
	}

	/**
	 * Callback function when a booking is processed by the payment provider.
	 */
	public function onDPPaymentCallBack(\stdClass $booking, array $data): bool
	{
		$app = $this->getApplication();
		if (!$app instanceof CMSWebApplicationInterface) {
			return false;
		}

		try {
			$provider = array_filter($this->onDPPaymentProviders(), static fn ($p): bool => $p->id == $booking->processor);
			if ($provider === []) {
				return false;
			}

			// Get the response from the callback
			$data = $this->finishTransaction($data, $booking, reset($provider));
		} catch (\Exception $exception) {
			$app->enqueueMessage(ucfirst($this->_name) . ': ' . $exception->getMessage(), 'error');
			$app->redirect(RouteHelper::getBookingRoute($booking) . '&layout=confirm');

			return false;
		}

		if ($data['state'] == 6) {
			$this->cancelPayment([], '');

			return false;
		}

		// Set the id of the booking
		$data['id'] = $booking->id;

		// Get the booking table
		$booking = $app->bootComponent('dpcalendar')->getMVCFactory()->createTable('Booking', 'Administrator');

		// Load the booking so we can update only the values from the data array
		$booking->load($data['id']);

		// Merge the old data with the new one
		$data = array_merge($booking->getData(), $data);

		// Save the data and make sure no valid event is triggered
		$app->bootComponent('dpcalendar')->getMVCFactory()->createModel('Booking', 'Administrator', ['event_after_save' => 'dontusethisevent'])
			->save($data);

		return true;
	}

	/**
	 * Get the payment providers of the plugin.
	 */
	public function onDPPaymentProviders(): array
	{
		if (!$this->params->get('providers')) {
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
			if (!empty($provider->icon) && strpos((string)$provider->icon, '.svg') > 0) {
				$provider->icon = JPATH_ROOT . '/' . $provider->icon;
			}

			$providers[] = $provider;
		}

		return $providers;
	}

	/**
	 * Canceling the payment will delete the booking.
	 */
	protected function cancelPayment(array $data, ?string $msg = null): void
	{
		$app = $this->getApplication();
		if (!$app instanceof CMSWebApplicationInterface) {
			return;
		}

		// Make sure we have a booking to cancel
		if (!isset($data['b_id'])) {
			$data['b_id'] = $app->getInput()->getInt('b_id', 0);
		}

		// Display the message and set it as failure
		if ($msg !== null) {
			$app->enqueueMessage($msg, 'error');
		}

		// Redirect to pay.cancel task
		$app->redirect(Route::_('index.php?option=com_dpcalendar&task=booking.paycancel&b_id=' . $data['b_id'], false));
	}
}
