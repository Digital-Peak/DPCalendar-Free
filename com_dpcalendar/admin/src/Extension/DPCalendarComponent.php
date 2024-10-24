<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2024 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Component\DPCalendar\Administrator\Extension;

use Joomla\CMS\Categories\CategoryServiceInterface;
use Joomla\CMS\Categories\CategoryServiceTrait;
use Joomla\CMS\Component\Router\RouterServiceInterface;
use Joomla\CMS\Component\Router\RouterServiceTrait;
use Joomla\CMS\Extension\BootableExtensionInterface;
use Joomla\CMS\Extension\MVCComponent;
use Joomla\CMS\Factory;
use Joomla\CMS\Fields\FieldsServiceInterface;
use Joomla\CMS\HTML\HTMLRegistryAwareTrait;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Tag\TagServiceInterface;
use Joomla\CMS\Tag\TagServiceTrait;
use Joomla\Database\DatabaseAwareTrait;
use Psr\Container\ContainerInterface;

class DPCalendarComponent extends MVCComponent implements
	CategoryServiceInterface,
	FieldsServiceInterface,
	RouterServiceInterface,
	TagServiceInterface,
	BootableExtensionInterface
{
	use RouterServiceTrait;
	use HTMLRegistryAwareTrait;
	use DatabaseAwareTrait;
	use CategoryServiceTrait, TagServiceTrait {
		CategoryServiceTrait::getTableNameForSection insteadof TagServiceTrait;
		CategoryServiceTrait::getStateColumnForSection insteadof TagServiceTrait;
	}

	public function boot(ContainerInterface $container): void
	{
		// Register the file itself for lazy class loading
		// @phpstan-ignore-next-line
		\JLoader::register('DPCalendarHelperRoute', JPATH_SITE . '/components/com_dpcalendar/helpers/route.php');

		require_once JPATH_ADMINISTRATOR . '/components/com_dpcalendar/vendor/autoload.php';
	}

	public function validateSection($section, $item = null)
	{
		if (Factory::getApplication()->isClient('site')) {
			// On the front end we need to map some sections
			$section = match ($section) {
				'form'  => 'event',
				default => null,
			};
		}

		if (!$section) {
			// We don't know other sections
			return null;
		}

		return $section;
	}

	public function getContexts(): array
	{
		Factory::getApplication()->getLanguage()->load('com_content', JPATH_ADMINISTRATOR);

		return [
			'com_dpcalendar.event'      => Text::_('COM_DPCALENDAR_FIELDS_SECTION_EVENT'),
			'com_dpcalendar.location'   => Text::_('COM_DPCALENDAR_FIELDS_SECTION_LOCATION'),
			'com_dpcalendar.ticket'     => Text::_('COM_DPCALENDAR_FIELDS_SECTION_TICKET'),
			'com_dpcalendar.booking'    => Text::_('COM_DPCALENDAR_FIELDS_SECTION_BOOKING'),
			'com_dpcalendar.categories' => Text::_('COM_DPCALENDAR_FIELDS_SECTION_CALENDAR')
		];
	}

	public function countItems(array $items, string $section): array
	{
		$db = $this->getDatabase();
		foreach ($items as $item) {
			$item->count_trashed     = 0;
			$item->count_archived    = 0;
			$item->count_unpublished = 0;
			$item->count_published   = 0;
			$query                   = $db->getQuery(true);
			$query->select('state, count(*) AS count')
				->from('#__dpcalendar_events')
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
