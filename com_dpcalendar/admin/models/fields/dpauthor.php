<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2015 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

defined('_JEXEC') or die();

use Joomla\CMS\Factory;
use Joomla\CMS\Form\Field\ListField;
use Joomla\CMS\Form\FormHelper;

if (version_compare(JVERSION, 4, '<') && !class_exists('\\Joomla\\CMS\\Form\\Field\\ListField', false)) {
	FormHelper::loadFieldClass('list');
	class_alias('JFormFieldList', '\\Joomla\\CMS\\Form\\Field\\ListField');
}

class JFormFieldDPAuthor extends ListField
{
	public $element;
	public $type = 'DPAuthor';

	protected function getOptions()
	{
		$authorField = 'created_by';
		if ($this->element['author_field']) {
			$authorField = $this->element['author_field'];
		}

		// Construct the query
		$db    = Factory::getDbo();
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
