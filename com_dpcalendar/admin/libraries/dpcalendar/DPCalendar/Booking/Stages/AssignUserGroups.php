<?php
/**
 * @package   DPCalendar
 * @author    Digital Peak http://www.digital-peak.com
 * @copyright Copyright (C) 2007 - 2020 Digital Peak. All rights reserved.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */
namespace DPCalendar\Booking\Stages;

defined('_JEXEC') or die();

use League\Pipeline\StageInterface;

class AssignUserGroups implements StageInterface
{
	public function __invoke($payload)
	{
		// Do not assign when state is not active and previous state was not active as well
		if ($payload->item->state != 1 || ($payload->oldItem && $payload->oldItem->state == 1)) {
			return $payload;
		}

		// If there is no user id, do not assign
		if (!$payload->item->user_id) {
			return $payload;
		}

		$groups = [];
		foreach ($payload->eventsWithTickets as $event) {
			$assignedGroups = $event->booking_assign_user_groups;
			if (!$assignedGroups) {
				continue;
			}

			$groups = array_merge($groups, is_string($assignedGroups) ? explode(',', $assignedGroups) : $assignedGroups);
		}

		foreach (array_unique($groups) as $group) {
			\JUserHelper::addUserToGroup($payload->item->user_id, $group);
		}

		return $payload;
	}
}
