<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2021 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Component\DPCalendar\Api\View\Locations;

defined('_JEXEC') or die;

use DigitalPeak\Component\DPCalendar\Api\Serializer\DPCalendar;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\View\JsonApiView as BaseApiView;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Component\Fields\Administrator\Helper\FieldsHelper;

class JsonapiView extends BaseApiView
{
	protected $fieldsToRenderItem = [
		'id',
		'typeAlias',
		'title',
		'description',
		'country',
		'province',
		'city',
		'zip',
		'street',
		'number',
		'rooms',
		'latitude',
		'longitude',
		'url',
		'color',
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
		'country',
		'province',
		'city',
		'zip',
		'street',
		'number',
		'rooms',
		'latitude',
		'longitude',
		'url',
		'color',
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
			$this->serializer = new DPCalendar($config['contentType']);
		}

		parent::__construct($config);
	}

	public function displayList(?array $items = null)
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

	/**
	 * @param \stdClass|false $item
	 */
	protected function prepareItem($item)
	{
		if ($item === false) {
			return parent::prepareItem(new \stdClass());
		}

		PluginHelper::importPlugin('content');
		PluginHelper::importPlugin('dpcalendar');

		$item->text = $item->description;
		Factory::getApplication()->triggerEvent('onContentPrepare', ['com_dpcalendar.location', &$item, &$item->params, 0]);
		$item->description = $item->text;

		foreach (FieldsHelper::getFields('com_dpcalendar.location', $item, true) as $field) {
			$item->{$field->name} = $field->apivalue ?? $field->rawvalue;
		}

		return parent::prepareItem($item);
	}
}
