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

class DPCalendarViewEvents extends BaseApiView
{
	protected $fieldsToRenderItem = [
		'id',
		'typeAlias',
		'title',
		'calid',
		'start_date',
		'end_date',
		'rrule',
		'exdates',
		'description',
		'capacity',
		'tags',
		'language',
		'state',
		'calendar',
		'images',
		'metakey',
		'metadesc',
		'metadata',
		'access',
		'featured',
		'alias',
		'note',
		'publish_up',
		'publish_down',
		'created',
		'created_by',
		'created_by_alias',
		'modified',
		'modified_by',
		'hits',
		'version',
		'featured_up',
		'featured_down'
	];

	protected $fieldsToRenderList = [
		'id',
		'typeAlias',
		'title',
		'calid',
		'start_date',
		'end_date',
		'rrule',
		'exdates',
		'description',
		'capacity',
		'tags',
		'language',
		'state',
		'calendar',
		'images',
		'metakey',
		'metadesc',
		'metadata',
		'access',
		'featured',
		'alias',
		'note',
		'publish_up',
		'publish_down',
		'created',
		'created_by',
		'created_by_alias',
		'modified',
		'modified_by',
		'hits',
		'version',
		'featured_up',
		'featured_down'
	];

	protected $relationship = [
		'calendar',
		'created_by',
		'tags',
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
		foreach (FieldsHelper::getFields('com_dpcalendar.event') as $field) {
			$this->fieldsToRenderList[] = $field->id;
		}

		return parent::displayList();
	}

	public function displayItem($item = null)
	{
		$this->relationship[] = 'modified_by';

		foreach (FieldsHelper::getFields('com_dpcalendar.event') as $field) {
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
		Factory::getApplication()->triggerEvent('onContentPrepare', ['com_dpcalendar.event', &$item, &$item->params, 0]);
		$item->description = $item->text;

		foreach (FieldsHelper::getFields('com_dpcalendar.event', $item, true) as $field) {
			$item->{$field->name} = $field->apivalue ?? $field->rawvalue;
		}

		$item->tags = [];
		if (!empty($item->tags->tags)) {
			$tagsIds   = explode(',', $item->tags->tags);
			$tagsNames = $item->tagsHelper->getTagNames($tagsIds);

			$item->tags = array_combine($tagsIds, $tagsNames);
		}

		return parent::prepareItem($item);
	}
}
