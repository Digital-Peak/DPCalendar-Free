<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2018 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */
defined('_JEXEC') or die();

if (!defined('DS')) {
	define('DS', DIRECTORY_SEPARATOR);
}

JLoader::register('DPCalendarHelperRoute', JPATH_SITE . '/components/com_dpcalendar/helpers/route.php');

JLoader::import('components.com_dpcalendar.libraries.vendor.autoload', JPATH_ADMINISTRATOR);

class DPCalendarHelper extends \DPCalendar\Helper\DPCalendarHelper
{
	public static function addSubmenu($vName = 'cpanel')
	{
		JHtmlSidebar::addEntry(JText::_('COM_DPCALENDAR_SUBMENU_CPANEL'), 'index.php?option=com_dpcalendar&view=cpanel', $vName == 'cpanel');
		JHtmlSidebar::addEntry(JText::_('COM_DPCALENDAR_SUBMENU_EVENTS'), 'index.php?option=com_dpcalendar&view=events', $vName == 'events');
		JHtmlSidebar::addEntry(
			JText::_('COM_DPCALENDAR_SUBMENU_CALENDARS'),
			'index.php?option=com_categories&extension=com_dpcalendar',
			$vName == 'categories'
		);
		JHtmlSidebar::addEntry(JText::_('COM_DPCALENDAR_SUBMENU_LOCATIONS'), 'index.php?option=com_dpcalendar&view=locations', $vName == 'locations');

		if (!self::isFree()) {
			JHtmlSidebar::addEntry(JText::_('COM_DPCALENDAR_SUBMENU_TICKETS'), 'index.php?option=com_dpcalendar&view=tickets', $vName == 'tickets');
			JHtmlSidebar::addEntry(
				JText::_('COM_DPCALENDAR_SUBMENU_BOOKINGS'),
				'index.php?option=com_dpcalendar&view=bookings',
				$vName == 'bookings'
			);
		}
		JHtmlSidebar::addEntry(
			JText::_('JGLOBAL_FIELDS'),
			'index.php?option=com_fields&context=com_dpcalendar.event',
			$vName == 'fields.fields'
		);
		JHtmlSidebar::addEntry(
			JText::_('JGLOBAL_FIELD_GROUPS'),
			'index.php?option=com_fields&view=groups&context=com_dpcalendar.event',
			$vName == 'fields.groups'
		);

		JHtmlSidebar::addEntry(JText::_('COM_DPCALENDAR_SUBMENU_TOOLS'), 'index.php?option=com_dpcalendar&view=tools', $vName == 'tools');
		JHtmlSidebar::addEntry(JText::_('COM_DPCALENDAR_SUBMENU_SUPPORT'), 'index.php?option=com_dpcalendar&view=support', $vName == 'support');
		if ($vName == 'categories') {
			JToolbarHelper::title(JText::sprintf('COM_CATEGORIES_CATEGORIES_TITLE', JText::_('com_dpcalendar')), 'dpcalendar-categories');
		}
	}

	public static function getActions($categoryId = 0)
	{
		$user   = JFactory::getUser();
		$result = new JObject();

		if (empty($categoryId)) {
			$assetName = 'com_dpcalendar';
			$level     = 'component';
		} else {
			$assetName = 'com_dpcalendar.category.' . (int)$categoryId;
			$level     = 'category';
		}

		$actions = JAccess::getActions('com_dpcalendar', $level);

		foreach ($actions as $action) {
			$result->set($action->name, $user->authorise($action->name, $assetName));
		}

		return $result;
	}

	public static function validateSection($section)
	{
		if (JFactory::getApplication()->isClient('site')) {
			// On the front end we need to map some sections
			switch ($section) {
				// Editing an article
				case 'form':
					$section = 'event';
					break;
				default:
					$section = null;
			}
		}

		if (!$section) {
			// We don't know other sections
			return null;
		}

		return $section;
	}

	public static function getContexts()
	{
		JFactory::getLanguage()->load('com_content', JPATH_ADMINISTRATOR);

		$contexts = array(
			'com_dpcalendar.event'    => JText::_('COM_DPCALENDAR_FIELDS_SECTION_EVENT'),
			'com_dpcalendar.location' => JText::_('COM_DPCALENDAR_FIELDS_SECTION_LOCATION'),
			'com_dpcalendar.ticket'   => JText::_('COM_DPCALENDAR_FIELDS_SECTION_TICKET'),
			'com_dpcalendar.booking'  => JText::_('COM_DPCALENDAR_FIELDS_SECTION_BOOKING')
		);

		return $contexts;
	}

	public static function getCalendarRoute($calId)
	{
		return DPCalendarHelperRoute::getCalendarRoute($calId);
	}

	public static function countItems(&$items)
	{
		$db = JFactory::getDbo();
		foreach ($items as $item) {
			$item->count_trashed     = 0;
			$item->count_archived    = 0;
			$item->count_unpublished = 0;
			$item->count_published   = 0;
			$query                   = $db->getQuery(true);
			$query->select('state, count(*) AS count')
				->from($db->qn('#__dpcalendar_events'))
				->where('catid = ' . (int)$item->id)
				->group('state');
			$db->setQuery($query);
			$events = $db->loadObjectList();
			foreach ($events as $event) {
				if ($event->state == 1) {
					$item->count_published = $event->count;
				}
				if ($event->state == 0) {
					$item->count_unpublished = $event->count;
				}
				if ($event->state == 2) {
					$item->count_archived = $event->count;
				}
				if ($event->state == -2) {
					$item->count_trashed = $event->count;
				}
			}
		}

		return $items;
	}

	public static function where()
	{
		$e     = new Exception();
		$trace = '<pre>' . $e->getTraceAsString() . '</pre>';

		echo $trace;

		return $trace;
	}
}
