<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2021 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Component\DPCalendar\Api\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\Filter\InputFilter;
use Joomla\CMS\MVC\Controller\ApiController;
use Joomla\Component\Fields\Administrator\Helper\FieldsHelper;

class LocationsController extends ApiController
{
	protected $contentType = 'locations';

	protected function save($recordKey = null)
	{
		$data = (array)json_decode($this->input->json->getRaw(), true, 512);

		foreach (FieldsHelper::getFields('com_dpcalendar.location') as $field) {
			if (!isset($data[$field->name])) {
				continue;
			}

			if (!isset($data['com_fields'])) {
				$data['com_fields'] = [];
			}

			$data['com_fields'][$field->name] = $data[$field->name];

			unset($data[$field->name]);
		}

		$this->input->set('data', $data);

		return parent::save($recordKey);
	}

	public function displayList()
	{
		$apiFilterInfo = $this->input->get('filter', [], 'array');
		$filter        = InputFilter::getInstance();

		if (\array_key_exists('search', $apiFilterInfo)) {
			$this->modelState->set('filter.search', $filter->clean($apiFilterInfo['search'], 'RAW'));
		}

		return parent::displayList();
	}
}
