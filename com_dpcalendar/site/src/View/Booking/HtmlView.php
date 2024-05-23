<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2014 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Component\DPCalendar\Site\View\Booking;

defined('_JEXEC') or die();

use DigitalPeak\Component\DPCalendar\Administrator\Helper\DPCalendarHelper;
use DigitalPeak\Component\DPCalendar\Administrator\View\BaseView;
use DigitalPeak\Component\DPCalendar\Site\Helper\RouteHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Form\FormField;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Joomla\Component\Content\Site\Helper\RouteHelper as HelperRouteHelper;
use Joomla\Registry\Registry;
use Joomla\Utilities\ArrayHelper;

class HtmlView extends BaseView
{
	/** @var ?\stdClass */
	protected $booking;

	/** @var array */
	protected $tickets;

	/** @var string */
	protected $cancelText;

	/** @var string */
	protected $orderText;

	/** @var array */
	protected $eventOptions;

	/** @var array */
	protected $ticketFormFields;

	/** @var ?FormField */
	protected $captchaField;

	/** @var array */
	protected $bookingFields;

	/** @var array */
	protected $terms;

	/** @var array */
	protected $paymentProviders;

	/** @var \stdClass */
	protected $paymentProvider;

	public function display($tpl = null): void
	{
		Form::addFieldPath(JPATH_ADMINISTRATOR . '/components/com_dpcalendar/models/forms');
		$model = Factory::getApplication()->bootComponent('dpcalendar')->getMVCFactory()->createModel('Booking', 'Administrator');
		$this->setModel($model, true);

		parent::display($tpl);
	}

	protected function init(): void
	{
		$app           = $this->app;
		$this->booking = $this->getModel()->getItem(['uid' => $app->getInput()->getString('uid', '')]) ?: null;

		// When no booking and a token is set, then load the booking from the token
		if ((!$this->booking || !$this->booking->id) && $token = $app->getInput()->get('token')) {
			$this->booking = $this->getModel()->getItem(['token' => $token]) ?: null;
			$this->tmpl .= '&token=' . $token;
		}

		if ($this->booking && in_array($this->booking->state, [1, 4, 5, 6, 7]) && in_array($this->getLayout(), ['review', 'confirm'])) {
			$this->app->redirect($this->router->getBookingRoute($this->booking));
		}

		if ($this->getLayout() == 'abort') {
			return;
		}

		if ($this->getLayout() == 'cancel') {
			$this->cancelText = HTMLHelper::_(
				'content.prepare',
				DPCalendarHelper::getStringFromParams(
					'cancel' . ($this->booking && $this->booking->id ? '' : 'paid') . 'text',
					'COM_DPCALENDAR_FIELD_CONFIG_BOOKINGSYS_CANCEL_' . ($this->booking && $this->booking->id ? '' : 'PAID_') . 'TEXT',
					$this->params
				)
			);

			return;
		}

		if (!$this->booking || !$this->booking->id) {
			$user = $this->getCurrentUser();
			if ($user->guest !== 0) {
				$this->app->enqueueMessage($this->translate('COM_DPCALENDAR_NOT_LOGGED_IN'), 'warning');
				$this->app->redirect(Route::_('index.php?option=com_users&view=login&return=' . base64_encode(Uri::getInstance())));

				return;
			}

			throw new \Exception($this->translate('COM_DPCALENDAR_ALERT_NO_AUTH'));
		}

		PluginHelper::importPlugin('dpcalendarpay');
		if ($this->getLayout() == 'order') {
			$message = HTMLHelper::_(
				'content.prepare',
				DPCalendarHelper::getStringFromParams('ordertext', 'COM_DPCALENDAR_FIELD_CONFIG_BOOKINGSYS_ORDER_TEXT', $this->params)
			);

			// The vars for the message
			$vars                   = ArrayHelper::fromObject($this->booking);
			$vars['currency']       = $this->params->get('currency', 'USD');
			$vars['currencySymbol'] = $this->params->get('currency_symbol', '$');
			$vars['bookingLink']    = RouteHelper::getBookingRoute($this->booking);
			$vars['sitename']       = $app->get('sitename');
			$this->orderText        = DPCalendarHelper::renderEvents([], $message, $this->params, $vars);
		}

		// Determine the payment provider
		foreach ($this->app->triggerEvent('onDPPaymentProviders') as $pluginProviders) {
			foreach ($pluginProviders as $provider) {
				if ($this->booking->processor == $provider->id) {
					$this->paymentProvider = $provider;
				}
			}
		}

		// Get the tickets of the booking
		$tickets = $this->booking->tickets;

		// Add some required variables to the display data
		$this->displayData['booking'] = $this->booking;
		$this->displayData['tickets'] = $tickets;

		// Initialize the amount of tickets
		$this->booking->amount_tickets = 0;
		$this->booking->amount_options = 0;

		// Some data required by the sub layouts
		$this->tickets          = [];
		$this->eventOptions     = [];
		$this->ticketFormFields = [];
		$this->captchaField     = null;
		$orderedOptions         = $this->booking->options ? explode(',', (string)$this->booking->options) : [];
		foreach ($tickets as $ticket) {
			// If the ticket belongs to this booking, increase the counter
			if ($ticket->booking_id == $this->booking->id) {
				$this->booking->amount_tickets++;
			}

			// Try to find the label of the ticket type
			$ticket->price_label = '';
			if ($ticket->event_prices && $ticket->price) {
				$ticket->event_prices = json_decode((string)$ticket->event_prices);

				if (array_key_exists($ticket->type, $ticket->event_prices->label) && $ticket->event_prices->label[$ticket->type]) {
					$ticket->price_label = $ticket->event_prices->label[$ticket->type];
				}
			}

			// Set the ticket data to create the form from, needed for custom fields
			$this->app->setUserState('com_dpcalendar.edit.ticket.data', (array)$ticket);
			// The form of the ticket
			$ticketForm = $this->app->bootComponent('dpcalendar')->getMVCFactory()->createModel('Ticket', 'Administrator', ['ignore_request' => true])->getForm();

			// JForm is caching the forms ba name only instead of name and options
			$p = new \ReflectionProperty(Form::class, 'options');
			$p->setAccessible(true);
			$o            = $p->getValue($ticketForm);
			$o['control'] = 'ticket-' . $ticket->id;
			$p->setValue($ticketForm, $o);

			// Prepare the form
			$ticketForm->setFieldAttribute('id', 'type', 'hidden');
			$ticketForm->bind($ticket);

			$reviewData = $this->app->getUserState('com_dpcalendar.booking.ticket.reviewdata', []);
			if (!empty($reviewData[$ticket->id])) {
				$ticketForm->bind($reviewData[$ticket->id]);
			}

			$fields = $ticketForm->getFieldset();

			// Remove not needed fields
			foreach ($fields as $index => $field) {
				if (in_array($field->fieldname, ['state', 'public', 'price', 'latitude', 'longitude'])) {
					unset($fields[$index]);
				}

				if ($field->fieldname == 'captcha') {
					$this->captchaField = $field;
					unset($fields[$index]);
				}
			}

			// Sort the fields
			DPCalendarHelper::sortFields($fields, $this->params->get('ticket_form_fields_order_', new \stdClass()));
			$this->ticketFormFields[$ticket->id] = $fields;

			// Group the tickets by event
			if (!array_key_exists($ticket->event_id, $this->tickets)) {
				$this->tickets[$ticket->event_id] = [];
			}
			$this->tickets[$ticket->event_id][] = $ticket;

			if (!$ticket->event_options) {
				continue;
			}

			// Prepare the options
			foreach (json_decode((string)$ticket->event_options) as $key => $option) {
				$key = preg_replace('/\D/', '', (string)$key);

				foreach ($orderedOptions as $o) {
					[$eventId, $type, $amount] = explode('-', $o);

					if ($eventId != $ticket->event_id || $type != $key || isset($this->eventOptions[$eventId][$type]) && $this->eventOptions[$eventId][$type] !== []) {
						continue;
					}

					if (!array_key_exists($eventId, $this->eventOptions)) {
						$this->eventOptions[$eventId] = [];
					}
					$this->eventOptions[$eventId][$type] = ['price' => $option->price, 'label' => $option->label, 'amount' => $amount];
					$this->booking->amount_options += $amount;
				}
			}
		}

		// Prepare the booking through Joomla events
		$this->booking->text = '';
		$this->app->triggerEvent('onContentPrepare', ['com_dpcalendar.booking', &$this->booking, &$this->params, 0]);

		$this->booking->displayEvent = new \stdClass();
		$results                     = $this->app->triggerEvent(
			'onContentBeforeDisplay',
			['com_dpcalendar.booking', &$this->booking, &$this->params, 0]
		);
		$this->booking->displayEvent->beforeDisplayContent = trim(implode("\n", $results));

		$results = $this->app->triggerEvent(
			'onContentAfterDisplay',
			['com_dpcalendar.booking', &$this->booking, &$this->params, 0]
		);
		$this->booking->displayEvent->afterDisplayContent = trim(implode("\n", $results));

		// Set up the fields
		$this->bookingFields = $this->app->bootComponent('dpcalendar')->getMVCFactory()->createModel('FieldsOrder', 'Administrator')->getBookingFields($this->booking, $this->params, $this->app);

		if ($this->getLayout() == 'confirm') {
			$model = $this->app->bootComponent('com_content')->getMVCFactory()->createModel('Article', 'Site');
			$model->setState('params', new Registry());
			$model->setState('filter.published', 1);

			$this->terms = [];
			foreach ($tickets as $t) {
				if ($t->event_terms) {
					// Fetching the article
					$article = $model->getItem($t->event_terms);

					if ($article instanceof \stdClass && !array_key_exists($article->id, $this->terms)) {
						$article->dp_terms_link = $this->router->route(
							HelperRouteHelper::getArticleRoute((int)$article->id, $article->catid, $article->language)
						);
						$this->terms[$article->id] = $article;
					}
				}
			}

			$activatedPlugins = [];
			foreach ($tickets as $t) {
				foreach ($t->event_payment_provider as $pluginName) {
					$activatedPlugins[$pluginName ?: 0] = true;
				}

				if ($activatedPlugins === []) {
					$activatedPlugins[0] = true;
				}
			}

			$this->paymentProviders = [];
			foreach ($this->app->triggerEvent('onDPPaymentProviders') as $pluginProviders) {
				foreach ($pluginProviders as $provider) {
					if (!array_key_exists(0, $activatedPlugins) && !array_key_exists($provider->id, $activatedPlugins)) {
						continue;
					}

					$this->paymentProviders[] = $provider;
				}
			}
		}

		parent::init();
	}
}
