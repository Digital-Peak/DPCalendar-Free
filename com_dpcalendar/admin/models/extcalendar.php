<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2014 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */
defined('_JEXEC') or die();

JLoader::import('joomla.application.component.modeladmin');

class DPCalendarModelExtcalendar extends JModelAdmin
{
	protected $text_prefix = 'COM_DPCALENDAR_EXTCALENDAR';

	public function save($data)
	{
		$app = JFactory::getApplication();

		// Alter the title for save as copy
		if ($app->input->get('task') == 'save2copy') {
			$title         = \Joomla\String\StringHelper::increment($data['title']);
			$alias         = \Joomla\String\StringHelper::increment($data['alias']);
			$data['title'] = $title;
			$data['alias'] = $alias;
			$data['state'] = 0;
		}

		return parent::save($data);
	}

	public function getTable($type = 'Extcalendar', $prefix = 'DPCalendarTable', $config = [])
	{
		return JTable::getInstance($type, $prefix, $config);
	}

	public function getForm($data = [], $loadData = true)
	{
		// Get the form.
		$form = $this->loadForm('com_dpcalendar.extcalendar', 'extcalendar', ['control' => 'jform', 'load_data' => $loadData]);
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

		if ($form->getFieldAttribute('action-edit', 'default', 'false', 'params') !== 'true') {
			$form->removeField('rules');
		}

		return $form;
	}

	protected function preprocessForm(JForm $form, $data, $group = 'content')
	{
		$plugin = JFactory::getApplication()->input->getWord('dpplugin');

		JFactory::getLanguage()->load('plg_dpcalendar_' . $plugin, JPATH_PLUGINS . '/dpcalendar/' . $plugin);
		$form->loadFile(JPATH_PLUGINS . '/dpcalendar/' . $plugin . '/forms/params.xml', false);

		return parent::preprocessForm($form, $data, $group);
	}

	protected function loadFormData()
	{
		$data = JFactory::getApplication()->getUserState('com_dpcalendar.edit.extcalendar.data', []);

		if (empty($data)) {
			$data = $this->getItem();
		}

		return $data;
	}

	protected function prepareTable($table)
	{
		$date = JFactory::getDate();
		$user = JFactory::getUser();

		$table->title = htmlspecialchars_decode($table->title, ENT_QUOTES);
		$table->alias = $table->alias ? JApplicationHelper::stringURLSafe($table->alias) : null;

		if (empty($table->alias)) {
			$table->alias = JApplicationHelper::stringURLSafe($table->title);
		}
		if (empty($table->plugin)) {
			$table->plugin = JFactory::getApplication()->input->getWord('dpplugin');
		}

		if (empty($table->id)) {
			// Set ordering to the last item if not set
			if (empty($table->ordering)) {
				$this->getDbo()->setQuery('SELECT MAX(ordering) FROM #__dpcalendar_extcalendars');
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

	public function cleanEventCache($plugin)
	{
		// Clean the Joomla cache
		$cache = JFactory::getCache('plg_dpcalendar_' . $plugin);
		if (!$cache->clean()) {
			return false;
		}

		// Clean the DB cache entries from the database
		JPluginHelper::importPlugin('dpcalendar');
		$tmp = JFactory::getApplication()->triggerEvent('onCalendarsFetch');
		if (!empty($tmp)) {
			$ids = [];
			foreach ($tmp as $calendars) {
				foreach ($calendars as $externalCalendar) {
					if ($externalCalendar->plugin_name != $plugin) {
						continue;
					}
					$ids[] = $externalCalendar->id;
				}
			}
			if ($ids) {
				// Delete the events
				$this->getDbo()->setQuery("delete from #__dpcalendar_events where catid in ('" . implode("','", $ids) . "')");
				$this->getDbo()->execute();

				// Delete the location associations
				$this->getDbo()->setQuery('delete from #__dpcalendar_events_location where event_id not in (select id from #__dpcalendar_events)');
				$this->getDbo()->execute();
				$this->getDbo()->setQuery('delete from #__dpcalendar_events_hosts where event_id not in (select id from #__dpcalendar_events)');
				$this->getDbo()->execute();

				// Clearing the sync token
				$this->getDbo()->setQuery("update #__dpcalendar_extcalendars set sync_date = null, sync_token = null where plugin = '" . $plugin . "'");
				$this->getDbo()->execute();
			}
		}

		return true;
	}
}
