<?php
/**
 * @package   DPCalendar
 * @author    Digital Peak http://www.digital-peak.com
 * @copyright Copyright (C) 2007 - 2020 Digital Peak. All rights reserved.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */
defined('_JEXEC') or die();

JLoader::register('ActionLogPlugin', JPATH_ADMINISTRATOR . '/components/com_actionlogs/libraries/actionlogplugin.php');

class PlgActionlogDPCalendar extends ActionLogPlugin
{
	public function onContentAfterSave($context, $item, $isNew)
	{
		$message = $this->getMessage($context, $item, $isNew ? 'add' : 'update');

		if (!$message) {
			return;
		}

		$this->addLog([$message], $isNew ? 'PLG_SYSTEM_ACTIONLOGS_CONTENT_ADDED' : 'PLG_SYSTEM_ACTIONLOGS_CONTENT_UPDATED', $context);
	}

	public function onContentAfterDelete($context, $item)
	{
		$message = $this->getMessage($context, $item, 'delete');

		if (!$message) {
			return;
		}

		$this->addLog([$message], 'PLG_SYSTEM_ACTIONLOGS_CONTENT_DELETED', $context);
	}

	private function getMessage($context, $item, $action)
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
