<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2017 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Plugin\Fields\DPCalendar\Extension;

defined('_JEXEC') or die();

use Joomla\CMS\Application\CMSApplicationInterface;
use Joomla\CMS\Form\Form;
use Joomla\Component\Fields\Administrator\Plugin\FieldsPlugin;

class DPCalendar extends FieldsPlugin
{
	public function onCustomFieldsPrepareDom($field, \DOMElement $parent, Form $form)
	{
		$fieldNode = parent::onCustomFieldsPrepareDom($field, $parent, $form);
		if (!$fieldNode instanceof \DOMElement) {
			return $fieldNode;
		}

		if ($field->type === 'dpcalendar') {
			$fieldNode->setAttribute('extension', 'com_dpcalendar');
			$fieldNode->setAttribute('addfieldprefix', 'DigitalPeak\\Component\\DPCalendar\\Administrator\\Field');
		}

		if ($field->type === 'dpevent') {
			$fieldNode->setAttribute('addfieldprefix', 'DigitalPeak\\Plugin\\Fields\\DPCalendar\\Field');
		}

		// If the field is not required add an empty option
		if (!$field->required) {
			$fieldNode->appendChild(new \DOMElement('option', ''));
		}

		return $fieldNode;
	}

	public function onCustomFieldsBeforePrepareField(string $context, mixed $event, mixed $field): void
	{
		if ($context !== 'com_dpcalendar.event' || !$field->params->get('dpcalendar_cf_only_tickets')) {
			return;
		}

		$user = $this->getApplication() instanceof CMSApplicationInterface ? $this->getApplication()->getIdentity() : null;
		if ($user === null) {
			$field->value = null;
			return;
		}

		// When the user is the author of the event or an admin then all is ok
		if ($event->created_by == $user->id || $user->authorise('dpcalendar.admin.book', 'com_dpcalendar.category.' . $event->catid)) {
			return;
		}

		$tickets = $event->tickets ?? [];

		// Get the tickets of the actual logged in user
		$myTickets = array_filter($tickets, static fn ($ticket): bool => !$user->guest && $user->id && $ticket->user_id == $user->id && $ticket->state == 1);

		if ($myTickets !== []) {
			return;
		}

		$field->value = null;
	}
}
