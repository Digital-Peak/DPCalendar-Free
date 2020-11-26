<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2014 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */
defined('_JEXEC') or die();

use Joomla\Registry\Registry;
use Joomla\Utilities\ArrayHelper;

class DPCalendarModelLocation extends JModelAdmin
{
	protected $text_prefix = 'COM_DPCALENDAR_LOCATION';

	protected $batch_commands = array(
		'language_id' => 'batchLanguage',
		'country_id'  => 'batchCountry',
	);

	protected function canDelete($record)
	{
		if (!empty($record->id) && $record->state != -2) {
			return false;
		}

		return parent::canDelete($record);
	}

	public function getItem($pk = null)
	{
		$item = parent::getItem($pk);

		if ($item->rooms && $item->rooms != '{}') {
			$item->rooms = json_decode($item->rooms);
		} else {
			$item->rooms = [];
		}

		if (empty($item->color)) {
			$item->color = \DPCalendar\Helper\Location::getColor($item);
		}

		$item->tags = new JHelperTags();
		$item->tags->getTagIds($item->id, 'com_dpcalendar.location');

		// Convert the params field to an array.
		$registry = new Registry();
		if (!empty($item->metadata)) {
			$registry->loadString($item->metadata);
		}
		$item->metadata = $registry;

		$user         = JFactory::getUser();
		$item->params = new Registry($item->params);
		$item->params->set('access-edit',
			$user->authorise('core.edit', 'com_dpcalendar')
			|| ($user->authorise('core.edit.own', 'com_dpcalendar') && $item->created_by == $user->id));
		$item->params->set('access-delete',
			$user->authorise('core.delete', 'com_dpcalendar')
			|| ($user->authorise('core.edit.own', 'com_dpcalendar') && $item->created_by == $user->id));

		if ($item->country) {
			$country = JModelLegacy::getInstance('Country', 'DPCalendarModel')->getItem($item->country);
			if ($country) {
				JFactory::getApplication()->getLanguage()->load('com_dpcalendar.countries', JPATH_ADMINISTRATOR . '/components/com_dpcalendar');
				$item->country_code       = $country->short_code;
				$item->country_code_value = JText::_('COM_DPCALENDAR_COUNTRY_' . $country->short_code);
			}
		}

		return $item;
	}

	public function getTable($type = 'Location', $prefix = 'DPCalendarTable', $config = [])
	{
		return JTable::getInstance($type, $prefix, $config);
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

		if (JFactory::getApplication()->isClient('site')) {
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
		$success = parent::save($data);

		if ($success && $this->getState('location.new') === true) {
			$data['id'] = $this->getState('location.id');
			JFactory::getApplication()->setUserState('dpcalendar.location.id', $data['id']);

			// Load the language
			JFactory::getLanguage()->load('com_dpcalendar', JPATH_ADMINISTRATOR . '/components/com_dpcalendar');

			// Create the subject
			$subject = DPCalendarHelper::renderEvents(
				[],
				JText::_('COM_DPCALENDAR_NOTIFICATION_LOCATION_SUBJECT_CREATE'),
				null,
				['location' => $data]
			);

			// Create the body
			$body = DPCalendarHelper::renderEvents(
				[],
				JText::_('COM_DPCALENDAR_NOTIFICATION_LOCATION_CREATE_BODY'),
				null,
				[
					'location'         => $data,
					'backLinkFull'     => DPCalendarHelperRoute::getLocationRoute((object)$data, true),
					'formattedAddress' => \DPCalendar\Helper\Location::format([(object)$data]),
					'sitename'         => JFactory::getConfig()->get('sitename'),
					'user'             => JFactory::getUser()->name
				]
			);

			// Send the notification to the groups
			DPCalendarHelper::sendMail($subject, $body, 'notification_groups_location_create');
		}

		return $success;
	}

	private function modifyField(JForm $form, $name)
	{
		$params = $this->getState('params');
		if (!$params) {
			$params = JComponentHelper::getParams('com_dpcalendar');

			if (JFactory::getApplication()->isClient('site')) {
				$params = JFactory::getApplication()->getParams();
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
		$data = JFactory::getApplication()->getUserState('com_dpcalendar.edit.location.data', []);
		if (empty($data)) {
			$data = $this->getItem();
		}

		if (is_array($data)) {
			$data = new \Joomla\CMS\Object\CMSObject($data);
		}

		// Forms can't handle registry objects on load
		if (isset($data->metadata) && $data->metadata instanceof Registry) {
			$data->metadata = $data->metadata->toArray();
		}

		$data->setProperties($this->getDefaultValues($data));

		$this->preprocessData('com_dpcalendar.location', $data);

		return $data;
	}

	private function getDefaultValues(JObject $item)
	{
		$params = $this->getParams();
		$data   = [];

		// Set the default values from the params
		if (!$item->get('country')) {
			$data['country'] = $params->get('location_form_default_country');
		}

		return $data;
	}

	protected function prepareTable($table)
	{
		$date = JFactory::getDate();
		$user = JFactory::getUser();

		$table->title = htmlspecialchars_decode($table->title, ENT_QUOTES);
		$table->alias = JApplicationHelper::stringURLSafe($table->alias);

		if (empty($table->alias)) {
			$table->alias = JApplicationHelper::stringURLSafe($table->title);
		}

		if (empty($table->latitude) && empty($table->longitude)) {
			$latLong          = \DPCalendar\Helper\Location::get(\DPCalendar\Helper\Location::format($table), false);
			$table->latitude  = $latLong->latitude;
			$table->longitude = $latLong->longitude;
		}

		if (empty($table->id)) {
			// Set ordering to the last item if not set
			if (empty($table->ordering)) {
				$db = JFactory::getDbo();
				$db->setQuery('SELECT MAX(ordering) FROM #__dpcalendar_locations');
				$max = $db->loadResult();

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
		$app = JFactory::getApplication();

		$pk = $app->input->getInt('l_id');
		$this->setState('location.id', $pk);
		$this->setState('form.id', $pk);

		$return = $app->input->getVar('return', null, 'default', 'base64');

		if (!JUri::isInternal(base64_decode($return))) {
			$return = null;
		}

		$this->setState('return_page', base64_decode($return));

		$params = JComponentHelper::getParams('com_dpcalendar');

		if ($app->isClient('site')) {
			$params = $app->getParams();
		}
		$this->setState('params', $params);
	}

	public function delete(&$pks)
	{
		$success = parent::delete($pks);

		if ($success) {
			// Delete associations
			$pks = (array)$pks;
			ArrayHelper::toInteger($pks);
			$this->_db->setQuery('delete from #__dpcalendar_events_location where location_id in (' . implode(',', $pks) . ')');
			$this->_db->query();
		}
	}

	public function getReturnPage()
	{
		return base64_encode($this->getState('return_page'));
	}

	protected function batchCountry($value, $pks, $contexts)
	{
		if (!$this->user->authorise('core.edit', 'com_dpcalendar')) {
			$this->setError(\JText::_('JLIB_APPLICATION_ERROR_BATCH_CANNOT_EDIT'));

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
		$params = $this->getState('params');

		if (!$params) {
			if (JFactory::getApplication()->isClient('site')) {
				$params = JFactory::getApplication()->getParams();
			} else {
				$params = JComponentHelper::getParams('com_dpcalendar');
			}
		}

		return $params;
	}
}
