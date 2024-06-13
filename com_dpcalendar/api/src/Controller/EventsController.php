<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2021 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Component\DPCalendar\Api\Controller;

defined('_JEXEC') or die;

use DigitalPeak\Component\DPCalendar\Administrator\Calendar\CalendarInterface;
use DigitalPeak\Component\DPCalendar\Administrator\Helper\DPCalendarHelper;
use Joomla\CMS\Filter\InputFilter;
use Joomla\CMS\MVC\Controller\ApiController;
use Joomla\Component\Fields\Administrator\Helper\FieldsHelper;
use Tobscure\JsonApi\Exception\InvalidParameterException;

class EventsController extends ApiController
{
	protected $contentType = 'events';

	protected function save($recordKey = null)
	{
		$data = (array)json_decode($this->input->json->getRaw(), true, 512);

		foreach (FieldsHelper::getFields('com_dpcalendar.event') as $field) {
			if (!isset($data[$field->name])) {
				continue;
			}

			if (!isset($data['com_fields'])) {
				$data['com_fields'] = [];
			}

			$data['com_fields'][$field->name] = $data[$field->name];

			unset($data[$field->name]);
		}

		if (!empty($data['calid']) && empty($data['catid'])) {
			$data['catid'] = $data['calid'];
		}

		if ($this->input->getMethod() === 'POST') {
			$calendar = $this->getModel('Calendar', 'Administrator')->getCalendar($data['catid'] ?? 0);
			if (!$calendar instanceof CalendarInterface) {
				throw new InvalidParameterException('Calendar not found', 404);
			}
		}

		$this->input->set('data', $data);

		return parent::save($recordKey);
	}

	public function displayList()
	{
		$apiFilterInfo = $this->input->get('filter', [], 'array');
		$filter        = InputFilter::getInstance();

		if (\array_key_exists('start-date', $apiFilterInfo)) {
			$this->modelState->set('list.start-date', DPCalendarHelper::getDate($filter->clean($apiFilterInfo['start-date'], 'STRING')));
		}

		if (\array_key_exists('end-date', $apiFilterInfo)) {
			$this->modelState->set('list.end-date', DPCalendarHelper::getDate($filter->clean($apiFilterInfo['end-date'], 'STRING')));
		}

		if (\array_key_exists('calids', $apiFilterInfo)) {
			$this->modelState->set('filter.calendars', explode(',', (string)$apiFilterInfo['calids']));
		}

		if (\array_key_exists('search', $apiFilterInfo)) {
			$this->modelState->set('filter.search', $filter->clean($apiFilterInfo['search'], 'RAW'));
		}

		if (\array_key_exists('expand', $apiFilterInfo)) {
			$this->modelState->set('filter.expand', $filter->clean($apiFilterInfo['expand'], 'BOOL'));
		}

		return parent::displayList();
	}
}
