<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2015 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */
defined('_JEXEC') or die();

JFormHelper::loadFieldClass('list');

class JFormFieldDPAuthor extends JFormFieldList
{
	public $type = 'DPAuthor';
	protected static $options = [];

	protected function getOptions()
	{
		// Accepted modifiers
		$hash = md5($this->element);

		if (! isset(static::$options[$hash])) {
			static::$options[$hash] = parent::getOptions();

			$options = [];

			$db = JFactory::getDbo();

			$authorField = 'created_by';
			if ($this->element['author_field']) {
				$authorField = $this->element['author_field'];
			}

			// Construct the query
			$query = $db->getQuery(true)
				->select('u.id AS value, u.name AS text')
				->from('#__users AS u')
				->join('INNER', '#__' . $this->element['table_name'] . ' AS c ON c.' . $authorField . ' = u.id')
				->group('u.id, u.name')
				->order('u.name');

			// Setup the query
			$db->setQuery($query);

			// Return the result
			if ($options = $db->loadObjectList()) {
				static::$options[$hash] = array_merge(static::$options[$hash], $options);
			}
		}

		return static::$options[$hash];
	}
}
