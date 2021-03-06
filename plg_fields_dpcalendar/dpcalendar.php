<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2017 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */
defined('_JEXEC') or die();

JLoader::import('components.com_fields.libraries.fieldsplugin', JPATH_ADMINISTRATOR);

class PlgFieldsDPCalendar extends FieldsPlugin
{
	public function onCustomFieldsPrepareDom($field, DOMElement $parent, JForm $form)
	{
		$fieldNode = parent::onCustomFieldsPrepareDom($field, $parent, $form);

		if (!$fieldNode) {
			return $fieldNode;
		}

		if ($field->type == 'dpcalendar') {
			$fieldNode->setAttribute('extension', 'com_dpcalendar');
			$fieldNode->setAttribute('addfieldpath', '/administrator/components/com_dpcalendar/models/fields');
		}

		// If the field is not required add an empty option
		if (!$field->required) {
			$fieldNode->appendChild(new DOMElement('option', ''));
		}

		return $fieldNode;
	}

	public function onCustomFieldsBeforePrepareField($context, $event, $field)
	{
		if ($context != 'com_dpcalendar.event' || !$field->params->get('dpcalendar_cf_only_tickets')) {
			return;
		}

		$user = JFactory::getUser();

		// When the user is the author of the event or an admin then all is ok
		if ($event->created_by == $user->id || $user->authorise('dpcalendar.admin.book', 'com_dpcalendar.' . $event->catid)) {
			return;
		}

		$tickets = isset($event->tickets) ? $event->tickets : [];

		// Get the tickets of the actual logged in user
		$myTickets = array_filter($tickets, function ($ticket) use ($user) {
			return !$user->guest && $user->id && $ticket->user_id == $user->id && $ticket->state == 1;
		});

		if ($myTickets) {
			return;
		}

		$field->value = null;
	}
}
