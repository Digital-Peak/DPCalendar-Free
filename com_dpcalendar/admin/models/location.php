<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2014 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

defined('_JEXEC') or die();

use DPCalendar\Helper\Location;
use Joomla\CMS\Application\ApplicationHelper;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Helper\TagsHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Object\CMSObject;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Uri\Uri;
use Joomla\Registry\Registry;
use Joomla\String\StringHelper;
use Joomla\Utilities\ArrayHelper;

class DPCalendarModelLocation extends AdminModel
{
	protected $text_prefix = 'COM_DPCALENDAR_LOCATION';

	protected $batch_commands = [
		'language_id' => 'batchLanguage',
		'country_id'  => 'batchCountry',
	];

	protected function canDelete($record)
	{
		if (!empty($record->id) && $record->state != -2 && !Factory::getApplication()->isClient('api')) {
			return false;
		}

		return parent::canDelete($record);
	}

	public function getItem($pk = null)
	{
		$item = parent::getItem($pk);

		if (!$item) {
			return $item;
		}

		$item->rooms = $item->rooms && $item->rooms != '{}' ? json_decode($item->rooms) : [];

		if (empty($item->color)) {
			$item->color = Location::getColor($item);
		}

		$item->tags = new TagsHelper();
		$item->tags->getTagIds($item->id, 'com_dpcalendar.location');

		// Convert the params field to an array.
		$registry = new Registry();
		if (!empty($item->metadata)) {
			$registry->loadString($item->metadata);
		}
		$item->metadata = $registry;

		$user         = Factory::getUser();
		$item->params = new Registry($item->params);
		$item->params->set(
			'access-edit',
			$user->authorise('core.edit', 'com_dpcalendar')
			|| ($user->authorise('core.edit.own', 'com_dpcalendar') && $item->created_by == $user->id)
		);
		$item->params->set(
			'access-delete',
			$user->authorise('core.delete', 'com_dpcalendar')
			|| ($user->authorise('core.edit.own', 'com_dpcalendar') && $item->created_by == $user->id)
		);

		if ($item->country) {
			$country = BaseDatabaseModel::getInstance('Country', 'DPCalendarModel')->getItem($item->country);
			if ($country) {
				Factory::getApplication()->getLanguage()->load('com_dpcalendar.countries', JPATH_ADMINISTRATOR . '/components/com_dpcalendar');
				$item->country_code       = $country->short_code;
				$item->country_code_value = Text::_('COM_DPCALENDAR_COUNTRY_' . $country->short_code);
			}
		}

		return $item;
	}

	public function getTable($type = 'Location', $prefix = 'DPCalendarTable', $config = [])
	{
		return parent::getTable($type, $prefix, $config);
	}

	public function getForm($data = [], $loadData = true, $controlName = 'jform')
	{
		// Get the form.
		$form = $this->loadForm('com_dpcalendar.location', 'location', ['control' => $controlName, 'load_data' => $loadData]);
		if (empty($form)) {
			return false;
		}

		// Modify the form based on access controls.
		if (!$this->canEditState((object)$data)) {
			// Disable fields for display.
			$form->setFieldAttribute('ordering', 'disabled', 'true');
			$form->setFieldAttribute('state', 'disabled', 'true');
			$form->setFieldAttribute('publish_up', 'disabled', 'true');
			$form->setFieldAttribute('publish_down', 'disabled', 'true');

			// Disable fields while saving.
			$form->setFieldAttribute('ordering', 'filter', 'unset');
			$form->setFieldAttribute('state', 'filter', 'unset');
			$form->setFieldAttribute('publish_up', 'filter', 'unset');
			$form->setFieldAttribute('publish_down', 'filter', 'unset');
		}

		if (!DPCalendarHelper::isCaptchaNeeded()) {
			$form->removeField('captcha');
		}

		if (Factory::getApplication()->isClient('site')) {
			$form->setFieldAttribute('id', 'type', 'hidden');
		}

		$this->modifyField($form, 'country');
		$this->modifyField($form, 'province');
		$this->modifyField($form, 'city');
		$this->modifyField($form, 'zip');
		$this->modifyField($form, 'street');
		$this->modifyField($form, 'number');
		$this->modifyField($form, 'url');

		return $form;
	}

	public function save($data)
	{
		$app   = Factory::getApplication();
		$input = $app->input;

		// Alter the title for save as copy
		if ($input->get('task') == 'save2copy') {
			$origTable = $this->getTable();

			$origTable->load($input->getInt('l_id'));
			$data['alias'] = $origTable->title === $data['title'] ? $origTable->alias : '';

			if ($data['title'] == $origTable->title) {
				[$title, $alias] = $this->findNewTitle($data['alias'], $data['title']);
				$data['title']   = $title;
				$data['alias']   = $alias;
			} elseif ($data['alias'] == $origTable->alias) {
				$data['alias'] = '';
			}
		}

		$success = parent::save($data);

		if ($success && $this->getState('location.new') === true) {
			$data['id'] = $this->getState('location.id');
			Factory::getApplication()->setUserState('dpcalendar.location.id', $data['id']);

			// Load the language
			Factory::getLanguage()->load('com_dpcalendar', JPATH_ADMINISTRATOR . '/components/com_dpcalendar');

			// Create the subject
			$subject = DPCalendarHelper::renderEvents(
				[],
				Text::_('COM_DPCALENDAR_NOTIFICATION_LOCATION_SUBJECT_CREATE'),
				null,
				['location' => $data]
			);

			// Create the body
			$body = DPCalendarHelper::renderEvents(
				[],
				Text::_('COM_DPCALENDAR_NOTIFICATION_LOCATION_CREATE_BODY'),
				null,
				[
					'location'         => $data,
					'backLinkFull'     => DPCalendarHelperRoute::getLocationRoute((object)$data, true),
					'formattedAddress' => Location::format([(object)$data]),
					'sitename'         => Factory::getApplication()->get('sitename'),
					'user'             => Factory::getUser()->name
				]
			);

			// Send the notification to the groups
			DPCalendarHelper::sendMail($subject, $body, 'notification_groups_location_create');
		}

		return $success;
	}

	private function modifyField(Form $form, $name)
	{
		$params = $this->getState('params');
		if (!$params) {
			$params = ComponentHelper::getParams('com_dpcalendar');

			if (Factory::getApplication()->isClient('site')) {
				$params = Factory::getApplication()->getParams();
			}
		}

		$state = $params->get('location_form_' . $name, 1);
		switch ($state) {
			case 0:
				$form->removeField($name);
				break;
			case 2:
				$form->setFieldAttribute($name, 'required', 'true');
				break;
		}
	}

	protected function loadFormData()
	{
		$data = Factory::getApplication()->getUserState(
			'com_dpcalendar.edit.location.data',
			Factory::getApplication()->getUserState('com_dpcalendar.edit.locationform.data', [])
		);
		if (empty($data)) {
			$data = $this->getItem();
		}

		if (is_array($data)) {
			$data = new CMSObject($data);
		}

		// Forms can't handle registry objects on load
		if (isset($data->metadata) && $data->metadata instanceof Registry) {
			$data->metadata = $data->metadata->toArray();
		}

		$data->setProperties($this->getDefaultValues($data));

		$this->preprocessData('com_dpcalendar.location', $data);

		return $data instanceof Table ? $data->getProperties() : $data;
	}

	private function getDefaultValues(CMSObject $item)
	{
		$params = $this->getParams();
		$data   = [];

		// Set the default values from the params
		if (!$item->get('country')) {
			$data['country'] = $params->get('location_form_default_country');
		}

		if (!$item->get('latitude')) {
			$data['latitude'] = $params->get('location_form_map_latitude', 47);
		}

		if (!$item->get('longitude')) {
			$data['longitude'] = $params->get('location_form_map_longitude', 4);
		}

		return $data;
	}

	protected function prepareTable($table)
	{
		$date = Factory::getDate();
		$user = Factory::getUser();

		$table->title = htmlspecialchars_decode($table->title, ENT_QUOTES);
		$table->alias = $table->alias ? ApplicationHelper::stringURLSafe($table->alias) : '';

		if (empty($table->alias)) {
			$table->alias = ApplicationHelper::stringURLSafe($table->title);
		}

		if (empty($table->latitude) && empty($table->longitude)) {
			$latLong          = Location::get(Location::format($table), false);
			$table->latitude  = $latLong->latitude;
			$table->longitude = $latLong->longitude;
		}

		if (empty($table->id)) {
			// Set ordering to the last item if not set
			if (empty($table->ordering)) {
				$this->getDbo()->setQuery('SELECT MAX(ordering) FROM #__dpcalendar_locations');
				$max = $this->getDbo()->loadResult();

				$table->ordering = $max + 1;
			} else {
				// Set the values
				$table->modified    = $date->toSql();
				$table->modified_by = $user->get('id');
			}

			// Increment the content version number.
			$table->version++;
		}

		if (!isset($table->state) && $this->canEditState($table)) {
			$table->state = 1;
		}
	}

	protected function populateState()
	{
		$app = Factory::getApplication();

		$pk = $app->input->getInt('l_id');
		$this->setState('location.id', $pk);
		$this->setState('form.id', $pk);

		$return = $app->input->get('return', null, 'default', 'base64');
		if ($return && !Uri::isInternal(base64_decode($return ?: ''))) {
			$return = null;
		}

		$this->setState('return_page', $return ? base64_decode($return ?: '') : null);

		$this->setState('params', method_exists($app, 'getParams') ? $app->getParams() : ComponentHelper::getParams('com_dpcalendar'));
	}

	public function delete(&$pks)
	{
		$success = parent::delete($pks);
		if ($success) {
			// Delete associations
			$pks = (array)$pks;
			ArrayHelper::toInteger($pks);
			$this->_db->setQuery('delete from #__dpcalendar_events_location where location_id in (' . implode(',', $pks) . ')');
			$this->_db->execute();
		}

		return $success;
	}

	public function getReturnPage()
	{
		return base64_encode($this->getState('return_page', '') ?: Uri::base(true));
	}

	protected function batchCountry($value, $pks, $contexts)
	{
		if (!$this->user->authorise('core.edit', 'com_dpcalendar')) {
			$this->setError(Text::_('JLIB_APPLICATION_ERROR_BATCH_CANNOT_EDIT'));

			return false;
		}

		ArrayHelper::toInteger($pks);
		$this->getDbo()->setQuery('update #__dpcalendar_locations set country = ' . (int)$value . ' where id in (' . implode(',', $pks) . ')');
		$this->getDbo()->execute();

		// Clean the cache
		$this->cleanCache();

		return true;
	}

	private function getParams()
	{
		if ($params = $this->getState('params')) {
			return $params;
		}

		if (Factory::getApplication()->isClient('site')) {
			return Factory::getApplication()->getParams();
		}

		return ComponentHelper::getParams('com_dpcalendar');
	}

	private function findNewTitle($alias, $title)
	{
		// Alter the title & alias
		$table      = $this->getTable();
		$aliasField = $table->getColumnAlias('alias');
		$titleField = $table->getColumnAlias('title');

		while ($table->load([$aliasField => $alias])) {
			if ($title === $table->$titleField) {
				$title = StringHelper::increment($title);
			}

			$alias = StringHelper::increment($alias, 'dash');
		}

		return [$title, $alias];
	}
}
