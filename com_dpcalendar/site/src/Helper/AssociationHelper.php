<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2026 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Component\DPCalendar\Site\Helper;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Associations;
use Joomla\CMS\Language\LanguageHelper;
use Joomla\CMS\Language\Multilanguage;
use Joomla\Component\Categories\Administrator\Helper\CategoryAssociationHelper;
use Joomla\Database\DatabaseInterface;

\defined('_JEXEC') or die;

abstract class AssociationHelper extends CategoryAssociationHelper
{
	public static function getAssociations(?int $id = 0, ?string $view = null, ?string $layout = null): array
	{
		$jinput = Factory::getApplication()->getInput();
		$view ??= $jinput->get('view');
		$component = $jinput->getCmd('option');
		$id        = $id === null || $id === 0 ? $jinput->getInt('id') : $id;

		if ($layout === null && $jinput->get('view') == $view && $component == 'com_dpcalendar') {
			$layout = $jinput->get('layout', '', 'string');
		}

		if ($view === 'event' && $id) {
			// @phpstan-ignore-next-line
			$user      = Factory::getUser();
			$groups    = implode(',', $user->getAuthorisedViewLevels());
			$db        = Factory::getContainer()->get(DatabaseInterface::class);
			$advClause = [];
			// Filter by user groups
			$advClause[] = 'c2.access IN (' . $groups . ')';
			// Filter by current language
			$advClause[] = 'c2.language != ' . $db->quote(Factory::getApplication()->getLanguage()->getTag());
			if (!$user->authorise('core.edit.state', 'com_dpcalendar') && !$user->authorise('core.edit', 'com_dpcalendar')) {
				// Filter by start and end dates.
				$date = Factory::getDate();

				$nowDate = $db->quote($date->toSql());

				$advClause[] = '(c2.publish_up IS NULL OR c2.publish_up <= ' . $nowDate . ')';
				$advClause[] = '(c2.publish_down IS NULL OR c2.publish_down >= ' . $nowDate . ')';

				// Filter by published
				$advClause[] = 'c2.state = 1';
			}
			$associations = Associations::getAssociations(
				'com_dpcalendar',
				'#__dpcalendar_events',
				'com_dpcalendar.item',
				$id,
				'id',
				'alias',
				'catid',
				$advClause
			);
			$return = [];
			foreach ($associations as $tag => $item) {
				$return[$tag] = RouteHelper::getEventRoute($item->id, ((int)$item->catid) . '', false, false) . '&lang=' . $item->language;
			}
			return $return;
		}

		if ($view === 'category' || $view === 'categories') {
			return self::getCategoryAssociations($id, 'com_dpcalendar', $layout);
		}

		return [];
	}

	public static function displayAssociations(int $id): array
	{
		$return = [];

		if ($associations = self::getAssociations($id, 'event')) {
			// @phpstan-ignore-next-line
			$levels    = Factory::getUser()->getAuthorisedViewLevels();
			$languages = LanguageHelper::getLanguages();

			foreach ($languages as $language) {
				// Do not display language when no association
				if (empty($associations[$language->lang_code])) {
					continue;
				}

				// Do not display language without frontend UI
				if (!\array_key_exists($language->lang_code, LanguageHelper::getInstalledLanguages(0))) {
					continue;
				}

				// Do not display language without specific home menu
				if (!\array_key_exists($language->lang_code, Multilanguage::getSiteHomePages())) {
					continue;
				}

				// Do not display language without authorized access level
				if (isset($language->access) && $language->access && !\in_array($language->access, $levels)) {
					continue;
				}

				$return[$language->lang_code] = ['item' => $associations[$language->lang_code], 'language' => $language];
			}
		}

		return $return;
	}
}
