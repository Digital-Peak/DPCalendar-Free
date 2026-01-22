<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2026 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Component\DPCalendar\Administrator\Service\HTML;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Associations;
use Joomla\CMS\Language\LanguageHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

\defined('_JEXEC') or die;

class AdministratorService
{
	public function __construct(private readonly DatabaseInterface $db)
	{

	}

	public function association(string $eventId): string
	{
		// Defaults
		$html = '';

		// Get the associations
		if ($associations = Associations::getAssociations('com_dpcalendar', '#__dpcalendar_events', 'com_dpcalendar.item', (int)$eventId)) {
			foreach ($associations as $tag => $associated) {
				$associations[$tag] = (int)$associated->id;
			}

			// Get the associated event items
			$db    = $this->db;
			$query = $db->getQuery(true)
				->select(
					[
						'c.id',
						'c.name', 'title',
						'l.sef', 'lang_sef',
						'lang_code',
						'cat.title', 'category_title',
						'l.image',
						'l.title', 'language_title',
					]
				)
				->from('#__dpcalendar_events c')
				->join('LEFT', '#__categories cat', 'cat.id = c.catid')
				->join('LEFT', '#__languages l', 'c.language = l.lang_code')
				->whereIn('c.id', array_values($associations))
				->where('c.id != :id')
				->bind(':id', $eventId, ParameterType::INTEGER);
			$db->setQuery($query);

			try {
				$items = $db->loadObjectList('id');
			} catch (\RuntimeException $e) {
				throw new \Exception($e->getMessage(), 500, $e);
			}

			if ($items) {
				$languages         = LanguageHelper::getContentLanguages([0, 1]);
				$content_languages = array_column($languages, 'lang_code');

				foreach ($items as &$item) {
					if (\in_array($item->lang_code, $content_languages)) {
						$text    = $item->lang_code;
						$url     = Route::_('index.php?option=com_dpcalendar&task=event.edit&id=' . (int)$item->id);
						$tooltip = '<strong>' . htmlspecialchars((string)$item->language_title, ENT_QUOTES, 'UTF-8') . '</strong><br>'
							. htmlspecialchars((string)$item->title, ENT_QUOTES, 'UTF-8') . '<br>' . Text::sprintf('JCATEGORY_SPRINTF', $item->category_title);
						$classes = 'badge bg-secondary';

						$item->link = '<a href="' . $url . '" class="' . $classes . '">' . $text . '</a>'
							. '<div role="tooltip" id="tip-' . (int)$eventId . '-' . (int)$item->id . '">' . $tooltip . '</div>';
					} else {
						// Display warning if Content Language is trashed or deleted
						Factory::getApplication()->enqueueMessage(Text::sprintf('JGLOBAL_ASSOCIATIONS_CONTENTLANGUAGE_WARNING', $item->lang_code), 'warning');
					}
				}
			}

			$html = LayoutHelper::render('joomla.content.associations', $items);
		}

		return $html;
	}
}
