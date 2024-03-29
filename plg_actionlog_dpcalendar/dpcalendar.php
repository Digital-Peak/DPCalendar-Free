<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2018 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

defined('_JEXEC') or die();

use Joomla\Component\Actionlogs\Administrator\Plugin\ActionLogPlugin;

if (version_compare(JVERSION, 4, '<') && !class_exists('\\Joomla\\Component\\Actionlogs\\Administrator\\Plugin\\ActionLogPlugin', false)) {
	JLoader::import('components.com_actionlogs.libraries.actionlogplugin', JPATH_ADMINISTRATOR);
	class_alias('ActionLogPlugin', '\\Joomla\\Component\\Actionlogs\\Administrator\\Plugin\\ActionLogPlugin');
}

class PlgActionlogDPCalendar extends ActionLogPlugin
{
	public function onContentAfterSave($context, $item, $isNew): void
	{
		$message = $this->getMessage($context, $item, $isNew ? 'add' : 'update');

		if (!$message) {
			return;
		}

		$this->addLog([$message], $isNew ? 'PLG_SYSTEM_ACTIONLOGS_CONTENT_ADDED' : 'PLG_SYSTEM_ACTIONLOGS_CONTENT_UPDATED', $context);
	}

	public function onContentAfterDelete($context, $item): void
	{
		$message = $this->getMessage($context, $item, 'delete');

		if (!$message) {
			return;
		}

		$this->addLog([$message], 'PLG_SYSTEM_ACTIONLOGS_CONTENT_DELETED', $context);
	}

	private function getMessage($context, $item, string $action)
	{
		if (strpos($context, 'com_dpcalendar.') !== 0) {
			return null;
		}

		$message = [
			'id'     => $item->id,
			'action' => $action,
			'type'   => 'PLG_ACTIONLOG_DPCALENDAR_TYPE_' . substr($context, 15)
		];

		if ($context == 'com_dpcalendar.event') {
			$message['title']    = $item->title;
			$message['itemlink'] = 'index.php?option=com_dpcalendar&task=event.edit&e_id=' . $item->id;
		}

		if ($context == 'com_dpcalendar.booking') {
			$message['title']    = $item->uid;
			$message['itemlink'] = 'index.php?option=com_dpcalendar&task=booking.edit&b_id=' . (int)$item->id;
		}

		if ($context == 'com_dpcalendar.ticket') {
			$message['title']    = $item->uid;
			$message['itemlink'] = 'index.php?option=com_dpcalendar&task=ticket.edit&t_id=' . (int)$item->id;
		}

		if ($context == 'com_dpcalendar.location') {
			$message['title']    = $item->title;
			$message['itemlink'] = 'index.php?option=com_dpcalendar&task=location.edit&l_id=' . (int)$item->id;
		}

		if ($context == 'com_dpcalendar.extcalendar') {
			$message['title'] = $item->title;
		}

		return $message;
	}
}
