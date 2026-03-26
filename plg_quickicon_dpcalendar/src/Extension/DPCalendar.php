<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2026 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Plugin\Quickicon\DPCalendar\Extension;

\defined('_JEXEC') or die();

use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Router\Route;
use Joomla\Event\SubscriberInterface;
use Joomla\Module\Quickicon\Administrator\Event\QuickIconsEvent;

class DPCalendar extends CMSPlugin implements SubscriberInterface
{
	protected $autoloadLanguage = true;

	public static function getSubscribedEvents(): array
	{
		return [ 'onGetIcons' => 'getIcons'];
	}

	public function getIcons(QuickIconsEvent $event): void
	{
		if ($event->getContext() !== 'site_quickicon') {
			return;
		}

		$result = $event->getArgument('result', []);

		$result[] = [
			[
				'image'   => 'icon-refresh',
				'link'    => Route::_('index.php?option=com_dpcalendar&view=events'),
				'linkadd' => Route::_('index.php?option=com_dpcalendar&task=event.add'),
				'name'    => 'PLG_QUICKICON_DPCALENDAR_EVENTS',
				'access'  => ['core.manage', 'com_dpcalendar', 'core.create', 'com_dpcalendar'],
				'group'   => 'MOD_QUICKICON_SITE'
			],
			[
				'image'   => 'icon-calendar-alt',
				'link'    => Route::_('index.php?option=com_categories&extension=com_dpcalendar'),
				'linkadd' => Route::_('index.php?option=com_categories&extension=com_dpcalendar&task=category.add'),
				'name'    => 'PLG_QUICKICON_DPCALENDAR_CALENDARS',
				'access'  => ['core.manage', 'com_dpcalendar', 'core.create', 'com_dpcalendar'],
				'group'   => 'MOD_QUICKICON_SITE'
			],
	];
		$event->setArgument('result', $result);
	}
}
