<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2021 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

defined('_JEXEC') or die;

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Form\Form;
use Joomla\CMS\MVC\Controller\ApiController;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Table\Table;
use Joomla\Component\Fields\Administrator\Helper\FieldsHelper;
use Joomla\Input\Input;

class DPCalendarController extends ApiController
{
	protected $contentType  = 'dpcalendar';
	protected $default_view = 'events';

	public function __construct($config = [], MVCFactoryInterface $factory = null, ?CMSApplication $app = null, ?Input $input = null)
	{
		parent::__construct($config, $factory, $app, $input);

		$this->contentType = $input->get('controller');

		// It needs to be DpcalendarModel
		Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_dpcalendar/tables', 'DpcalendarTable');
		BaseDatabaseModel::addIncludePath(JPATH_SITE . '/components/com_dpcalendar/models', 'DpcalendarModel');
		BaseDatabaseModel::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_dpcalendar/models', 'DpcalendarModel');
		Form::addFormPath(JPATH_ADMINISTRATOR . '/components/com_dpcalendar/models/forms');

		$this->modelState->set('list.start-date', $input->get('date-start', DPCalendarHelper::getDate()->format('c')));
		if ($input->get('date-end')) {
			$this->modelState->set('list.end-date', $app->input->get('date-end'));
		}
	}

	protected function save($recordKey = null)
	{
		$data = (array) json_decode($this->input->json->getRaw(), true);

		foreach (FieldsHelper::getFields('com_dpcalendar.event') as $field) {
			if (!isset($data[$field->name])) {
				continue;
			}

			!isset($data['com_fields']) && $data['com_fields'] = [];

			$data['com_fields'][$field->name] = $data[$field->name];

			unset($data[$field->name]);
		}

		$this->input->set('data', $data);

		return parent::save($recordKey);
	}

	public function getModel($name = '', $prefix = 'DPCalendarModel', $config = [])
	{
		if ($name === 'event') {
			$name = 'adminevent';
		}
		return parent::getModel($name, $prefix, $config);
	}
}
