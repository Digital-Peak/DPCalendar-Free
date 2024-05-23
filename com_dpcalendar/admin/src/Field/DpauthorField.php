<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2015 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Component\DPCalendar\Administrator\Field;

defined('_JEXEC') or die();

use Joomla\CMS\Form\Field\ListField;

class DPAuthorField extends ListField
{
	public $type = 'DPAuthor';

	protected function getOptions(): array
	{
		$authorField = 'created_by';
		if ($this->element['author_field'] !== null) {
			$authorField = $this->element['author_field'];
		}

		// Construct the query
		$db    = $this->getDatabase();
		$query = $db->getQuery(true)
					->select('u.id AS value, u.name AS text')
					->from('#__users AS u')
					->join('INNER', '#__' . $this->element['table_name'] . ' AS c ON c.' . $authorField . ' = u.id')
					->group('u.id, u.name')
					->order('u.name');

		// Setup the query
		$db->setQuery($query);

		return array_merge(parent::getOptions(), $db->loadObjectList());
	}
}
