<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2014 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Component\DPCalendar\Administrator\Model;

\defined('_JEXEC') or die();

use DigitalPeak\Component\DPCalendar\Administrator\Helper\DPCalendarHelper;
use DigitalPeak\Component\DPCalendar\Administrator\Table\BasicTable;
use DigitalPeak\Component\DPCalendar\Administrator\Table\LocationTable;
use DigitalPeak\Component\DPCalendar\Site\Helper\RouteHelper;
use Joomla\CMS\Application\ApplicationHelper;
use Joomla\CMS\Application\CMSWebApplicationInterface;
use Joomla\CMS\Application\SiteApplication;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Helper\TagsHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\Uri\Uri;
use Joomla\Registry\Registry;
use Joomla\String\StringHelper;
use Joomla\Utilities\ArrayHelper;

class LocationModel extends AdminModel
{
	protected $text_prefix = 'COM_DPCALENDAR_LOCATION';

	protected $batch_commands = [
		'language_id' => 'batchLanguage',
		'country_id'  => 'batchCountry',
	];

	protected function canDelete($record)
	{
		if (!empty($record->state) && $record->state != -2 && !Factory::getApplication()->isClient('api')) {
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

		$item->rooms = $item->rooms && $item->rooms != '{}' ? json_decode((string)$item->rooms) : [];

		if (empty($item->color)) {
			$item->color = $this->bootComponent('dpcalendar')->getMVCFactory()->createModel('Geo', 'Administrator')->getColor($item);
		}

		$item->tags = new TagsHelper();
		$item->tags->getTagIds($item->id, 'com_dpcalendar.location');

		// Convert the params field to an array.
		$registry = new Registry();
		if (!empty($item->metadata)) {
			$registry->loadString($item->metadata);
		}
		$item->metadata = $registry;

		$user         = $this->getCurrentUser();
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
			$country = $this->bootComponent('dpcalendar')->getMVCFactory()->createModel('Country', 'Administrator')->getItem($item->country);
			if ($country) {
				Factory::getApplication()->getLanguage()->load('com_dpcalendar.countries', JPATH_ADMINISTRATOR . '/components/com_dpcalendar');
				$item->country_code       = $country->short_code;
				$item->country_code_value = Text::_('COM_DPCALENDAR_COUNTRY_' . $country->short_code);
			}
		}

		return $item;
	}

	public function getTable($type = 'Location', $prefix = 'Administrator', $config = [])
	{
		return parent::getTable($type, $prefix, $config);
	}

	public function getForm($data = [], $loadData = true, string  $controlName = 'jform')
	{
		// Get the form.
		$form = $this->loadForm('com_dpcalendar.location', 'location', ['control' => $controlName, 'load_data' => $loadData]);

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
		$input = $app->getInput();

		// Alter the title for save as copy
		if ($input->get('task') == 'save2copy') {
			$origTable = $this->getTable();

			$origTable->load($input->getInt('l_id', 0));
			$data['alias'] = $origTable->title === $data['title'] ? $origTable->alias : '';

			if ($data['title'] == $origTable->title) {
				[$title, $alias] = $this->findNewTitle($data['alias'], $data['title']);
				$data['title']   = $title;
				$data['alias']   = $alias;
			} elseif ($data['alias'] == $origTable->alias) {
				$data['alias'] = '';
			}
		}

		if (!empty($data['country']) && !is_numeric($data['country'])) {
			$country = $this->bootComponent('dpcalendar')->getMVCFactory()->createModel('Country', 'Administrator', ['ignore_request' => true])->getItem(['short_code' => $data['country']]);
			if ($country && $country->id) {
				$data['country'] = $country->id;
			}
		}

		$success = parent::save($data);

		if ($success && $this->getState('location.new') === true) {
			$data['id'] = $this->getState('location.id');
			if ($app instanceof CMSWebApplicationInterface) {
				$app->setUserState('dpcalendar.location.id', $data['id']);
			}

			// Load the language
			$app->getLanguage()->load('com_dpcalendar', JPATH_ADMINISTRATOR . '/components/com_dpcalendar');

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
					'backLinkFull'     => RouteHelper::getLocationRoute((object)$data, true),
					'formattedAddress' => $this->bootComponent('dpcalendar')->getMVCFactory()->createModel('Geo', 'Administrator')->format([(object)$data]),
					'sitename'         => Factory::getApplication()->get('sitename'),
					'user'             => $this->getCurrentUser()->name
				]
			);

			// Send the notification to the groups
			DPCalendarHelper::sendMail($subject, $body, 'notification_groups_location_create');
		}

		return $success;
	}

	private function modifyField(Form $form, string $name): void
	{
		$params = $this->getState('params');
		if (!$params) {
			$params = ComponentHelper::getParams('com_dpcalendar');

			$app = Factory::getApplication();
			if ($app instanceof SiteApplication) {
				$params = $app->getParams();
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
		$app  = Factory::getApplication();
		$data = $app instanceof CMSWebApplicationInterface ? $app->getUserState(
			'com_dpcalendar.edit.location.data',
			$app->getUserState('com_dpcalendar.edit.locationform.data', [])
		) : [];

		if (empty($data)) {
			$data = $this->getItem();
		}

		if (\is_array($data)) {
			$data = (object)$data;
		}

		// Forms can't handle registry objects on load
		if (isset($data->metadata) && $data->metadata instanceof Registry) {
			$data->metadata = $data->metadata->toArray();
		}

		foreach ($this->getDefaultValues($data) as $key => $value) {
			$data->{$key} = $value;
		}

		$this->preprocessData('com_dpcalendar.location', $data);

		return $data instanceof BasicTable ? $data->getData() : $data;
	}

	private function getDefaultValues(\stdClass $item): array
	{
		$params = $this->getParams();
		$data   = [];

		// Set the default values from the params
		if (!$item->country) {
			$data['country'] = $params->get('location_form_default_country');
		}

		if (!$item->latitude) {
			$data['latitude'] = $params->get('location_form_map_latitude', 47);
		}

		if (!$item->longitude) {
			$data['longitude'] = $params->get('location_form_map_longitude', 4);
		}

		return $data;
	}

	/**
	 * @param LocationTable $table
	 */
	protected function prepareTable($table)
	{
		$date = Factory::getDate();
		$user = $this->getCurrentUser();

		$table->title = htmlspecialchars_decode($table->title, ENT_QUOTES);
		$table->alias = $table->alias ? ApplicationHelper::stringURLSafe($table->alias) : '';

		if (empty($table->alias)) {
			$table->alias = ApplicationHelper::stringURLSafe($table->title);
		}

		if (empty($table->latitude) && empty($table->longitude)) {
			$latLong          = $this->bootComponent('dpcalendar')->getMVCFactory()->createModel('Geo', 'Administrator')->getLocation($this->bootComponent('dpcalendar')->getMVCFactory()->createModel('Geo', 'Administrator')->format($table), false);
			$table->latitude  = $latLong->latitude;
			$table->longitude = $latLong->longitude;
		}

		if (empty($table->id)) {
			// Set ordering to the last item if not set
			if (empty($table->ordering)) {
				$this->getDatabase()->setQuery('SELECT MAX(ordering) FROM #__dpcalendar_locations');
				$max = $this->getDatabase()->loadResult();

				$table->ordering = $max + 1;
			} else {
				// Set the values
				$table->modified    = $date->toSql();
				$table->modified_by = $user->id;
			}

			// Increment the content version number.
			$table->version++;
		}

		if (!empty($table->state)) {
			return;
		}

		if (!$this->canEditState($table)) {
			return;
		}

		$table->state = 1;
	}

	protected function populateState()
	{
		$app = Factory::getApplication();

		$pk = $app->getInput()->getInt('l_id', 0);
		$this->setState('location.id', $pk);
		$this->setState('form.id', $pk);

		$return = $app->getInput()->get('return', '', 'base64');
		if ($return && !Uri::isInternal(base64_decode((string)$return))) {
			$return = null;
		}

		$this->setState('return_page', base64_decode((string)($return ?: '')));

		$this->setState('params', $app instanceof SiteApplication ? $app->getParams() : ComponentHelper::getParams('com_dpcalendar'));
	}

	public function delete(&$pks)
	{
		$success = parent::delete($pks);
		if ($success) {
			// Delete associations
			$pks = ArrayHelper::toInteger((array)$pks);
			$this->getDatabase()->setQuery('delete from #__dpcalendar_events_location where location_id in (' . implode(',', $pks) . ')');
			$this->getDatabase()->execute();
		}

		return $success;
	}

	public function getReturnPage(): string
	{
		return base64_encode((string)($this->getState('return_page', '') ?: Uri::base(true)));
	}

	protected function batchCountry(string $value, array $pks): bool
	{
		if (!$this->getCurrentUser()->authorise('core.edit', 'com_dpcalendar')) {
			throw new \Exception(Text::_('JLIB_APPLICATION_ERROR_BATCH_CANNOT_EDIT'));
		}

		$pks = ArrayHelper::toInteger($pks);
		$this->getDatabase()->setQuery('update #__dpcalendar_locations set country = ' . (int)$value . ' where id in (' . implode(',', $pks) . ')');
		$this->getDatabase()->execute();

		// Clean the cache
		$this->cleanCache();

		return true;
	}

	private function getParams(): Registry
	{
		if ($params = $this->getState('params')) {
			return $params;
		}

		$app = Factory::getApplication();
		if ($app instanceof SiteApplication) {
			return $app->getParams();
		}

		return ComponentHelper::getParams('com_dpcalendar');
	}

	private function findNewTitle(string $alias, string $title): array
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
