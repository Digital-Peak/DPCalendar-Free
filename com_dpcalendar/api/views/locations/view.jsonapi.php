<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2021 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\View\JsonApiView as BaseApiView;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Component\Fields\Administrator\Helper\FieldsHelper;

class DPCalendarViewLocations extends BaseApiView
{
	protected $fieldsToRenderItem = [
		'id',
		'typeAlias',
		'title',
		'description',
		'language',
		'state',
		'metakey',
		'metadesc',
		'metadata',
		'access',
		'alias',
		'publish_up',
		'publish_down',
		'created',
		'created_by',
		'created_by_alias',
		'modified',
		'modified_by'
	];

	protected $fieldsToRenderList = [
		'id',
		'typeAlias',
		'title',
		'description',
		'language',
		'state',
		'metakey',
		'metadesc',
		'metadata',
		'access',
		'alias',
		'publish_up',
		'publish_down',
		'created',
		'created_by',
		'created_by_alias',
		'modified',
		'modified_by'
	];

	public function __construct($config = [])
	{
		if (array_key_exists('contentType', $config)) {
			$this->serializer = new DPCalendarSerializer($config['contentType']);
		}

		parent::__construct($config);
	}

	public function displayList(array $items = null)
	{
		foreach (FieldsHelper::getFields('com_dpcalendar.location') as $field) {
			$this->fieldsToRenderList[] = $field->id;
		}

		return parent::displayList();
	}

	public function displayItem($item = null)
	{
		$this->relationship[] = 'modified_by';

		foreach (FieldsHelper::getFields('com_dpcalendar.location') as $field) {
			$this->fieldsToRenderItem[] = $field->name;
		}

		return parent::displayItem();
	}

	protected function prepareItem($item)
	{
		if (!$item) {
			return;
		}

		PluginHelper::importPlugin('content');
		PluginHelper::importPlugin('dpcalendar');

		$item->text = $item->description;
		Factory::getApplication()->triggerLocation('onContentPrepare', ['com_dpcalendar.location', &$item, &$item->params, 0]);
		$item->description = $item->text;

		foreach (FieldsHelper::getFields('com_dpcalendar.location', $item, true) as $field) {
			$item->{$field->name} = isset($field->apivalue) ? $field->apivalue : $field->rawvalue;
		}

		return parent::prepareItem($item);
	}
}
