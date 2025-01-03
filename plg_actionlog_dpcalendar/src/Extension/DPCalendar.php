<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2018 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Plugin\Actionlog\DPCalendar\Extension;

\defined('_JEXEC') or die();

use Joomla\Component\Actionlogs\Administrator\Plugin\ActionLogPlugin;

class DPCalendar extends ActionLogPlugin
{
	public function onContentAfterSave(string $context, mixed $item, bool $isNew): void
	{
		$message = $this->getMessage($context, $item, $isNew ? 'add' : 'update');

		if ($message === []) {
			return;
		}

		$this->addLog([$message], $isNew ? 'PLG_SYSTEM_ACTIONLOGS_CONTENT_ADDED' : 'PLG_SYSTEM_ACTIONLOGS_CONTENT_UPDATED', $context);
	}

	public function onContentAfterDelete(string $context, mixed $item): void
	{
		$message = $this->getMessage($context, $item, 'delete');

		if ($message === []) {
			return;
		}

		$this->addLog([$message], 'PLG_SYSTEM_ACTIONLOGS_CONTENT_DELETED', $context);
	}

	private function getMessage(string $context, mixed $item, string $action): array
	{
		if (!str_starts_with($context, 'com_dpcalendar.')) {
			return [];
		}

		$message = [
			'id'     => $item->id,
			'action' => $action,
			'type'   => 'PLG_ACTIONLOG_DPCALENDAR_TYPE_' . substr($context, 15)
		];

		if ($context === 'com_dpcalendar.event') {
			$message['title']    = $item->title;
			$message['itemlink'] = 'index.php?option=com_dpcalendar&task=event.edit&e_id=' . $item->id;
		}

		if ($context === 'com_dpcalendar.booking') {
			$message['title']    = $item->uid;
			$message['itemlink'] = 'index.php?option=com_dpcalendar&task=booking.edit&b_id=' . (int)$item->id;
		}

		if ($context === 'com_dpcalendar.ticket') {
			$message['title']    = $item->uid;
			$message['itemlink'] = 'index.php?option=com_dpcalendar&task=ticket.edit&t_id=' . (int)$item->id;
		}

		if ($context === 'com_dpcalendar.location') {
			$message['title']    = $item->title;
			$message['itemlink'] = 'index.php?option=com_dpcalendar&task=location.edit&l_id=' . (int)$item->id;
		}

		if ($context === 'com_dpcalendar.extcalendar') {
			$message['title'] = $item->title;
		}

		return $message;
	}
}
