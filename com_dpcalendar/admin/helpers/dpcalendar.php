<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2014 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

defined('_JEXEC') or die();

use DPCalendar\Helper\DPCalendarHelper as Helper;
use Joomla\CMS\Access\Access;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\Helpers\Sidebar;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Object\CMSObject;
use Joomla\CMS\Toolbar\ToolbarHelper;

JLoader::register('DPCalendarHelperRoute', JPATH_SITE . '/components/com_dpcalendar/helpers/route.php');

JLoader::import('components.com_dpcalendar.vendor.autoload', JPATH_ADMINISTRATOR);

class DPCalendarHelper extends Helper
{
	public static function addSubmenu($vName = 'cpanel'): void
	{
		Sidebar::addEntry(Text::_('COM_DPCALENDAR_SUBMENU_CPANEL'), 'index.php?option=com_dpcalendar&view=cpanel', $vName == 'cpanel');
		Sidebar::addEntry(Text::_('COM_DPCALENDAR_SUBMENU_EVENTS'), 'index.php?option=com_dpcalendar&view=events', $vName == 'events');
		Sidebar::addEntry(
			Text::_('COM_DPCALENDAR_SUBMENU_CALENDARS'),
			'index.php?option=com_categories&extension=com_dpcalendar',
			$vName == 'categories'
		);
		Sidebar::addEntry(Text::_('COM_DPCALENDAR_SUBMENU_LOCATIONS'), 'index.php?option=com_dpcalendar&view=locations', $vName == 'locations');

		if (!self::isFree()) {
			Sidebar::addEntry(Text::_('COM_DPCALENDAR_SUBMENU_TICKETS'), 'index.php?option=com_dpcalendar&view=tickets', $vName == 'tickets');
			Sidebar::addEntry(
				Text::_('COM_DPCALENDAR_SUBMENU_BOOKINGS'),
				'index.php?option=com_dpcalendar&view=bookings',
				$vName == 'bookings'
			);
			Sidebar::addEntry(
				Text::_('COM_DPCALENDAR_SUBMENU_COUPONS'),
				'index.php?option=com_dpcalendar&view=coupons',
				$vName == 'coupons'
			);
			Sidebar::addEntry(
				Text::_('COM_DPCALENDAR_SUBMENU_TAXRATES'),
				'index.php?option=com_dpcalendar&view=taxrates',
				$vName == 'taxrates'
			);
			Sidebar::addEntry(
				Text::_('COM_DPCALENDAR_SUBMENU_COUNTRIES'),
				'index.php?option=com_dpcalendar&view=countries',
				$vName == 'countries'
			);
		}
		Sidebar::addEntry(
			Text::_('JGLOBAL_FIELDS'),
			'index.php?option=com_fields&context=com_dpcalendar.event',
			$vName == 'fields.fields'
		);
		Sidebar::addEntry(
			Text::_('JGLOBAL_FIELD_GROUPS'),
			'index.php?option=com_fields&view=groups&context=com_dpcalendar.event',
			$vName == 'fields.groups'
		);

		Sidebar::addEntry(Text::_('COM_DPCALENDAR_SUBMENU_TOOLS'), 'index.php?option=com_dpcalendar&view=tools', $vName == 'tools');
		if ($vName == 'categories') {
			ToolbarHelper::title(Text::sprintf('COM_CATEGORIES_CATEGORIES_TITLE', Text::_('com_dpcalendar')), 'dpcalendar-categories');
		}
	}

	public static function getActions($categoryId = 0): CMSObject
	{
		$user   = Factory::getUser();
		$result = new CMSObject();

		if (empty($categoryId)) {
			$assetName = 'com_dpcalendar';
			$level     = 'component';
		} else {
			$assetName = 'com_dpcalendar.category.' . (int)$categoryId;
			$level     = 'category';
		}

		$actions = Access::getActionsFromFile(
			JPATH_ADMINISTRATOR . '/components/com_dpcalendar/access.xml',
			"/access/section[@name='" . $level . "']/"
		);

		foreach ($actions as $action) {
			$result->set($action->name, $user->authorise($action->name, $assetName));
		}

		return $result;
	}

	public static function validateSection($section)
	{
		if (Factory::getApplication()->isClient('site')) {
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

	public static function getContexts(): array
	{
		Factory::getLanguage()->load('com_content', JPATH_ADMINISTRATOR);

		return [
			'com_dpcalendar.event'      => Text::_('COM_DPCALENDAR_FIELDS_SECTION_EVENT'),
			'com_dpcalendar.location'   => Text::_('COM_DPCALENDAR_FIELDS_SECTION_LOCATION'),
			'com_dpcalendar.ticket'     => Text::_('COM_DPCALENDAR_FIELDS_SECTION_TICKET'),
			'com_dpcalendar.booking'    => Text::_('COM_DPCALENDAR_FIELDS_SECTION_BOOKING'),
			'com_dpcalendar.categories' => Text::_('COM_DPCALENDAR_FIELDS_SECTION_CALENDAR')
		];
	}

	public static function getCalendarRoute($calId): string
	{
		return DPCalendarHelperRoute::getCalendarRoute($calId);
	}

	public static function countItems(&$items)
	{
		$db = Factory::getDbo();
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
}
