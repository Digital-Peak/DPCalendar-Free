<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2014 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Component\DPCalendar\Administrator\Model;

\defined('_JEXEC') or die();

use DigitalPeak\Component\DPCalendar\Administrator\Table\BasicTable;
use DigitalPeak\Component\DPCalendar\Administrator\Table\ExtcalendarTable;
use Joomla\CMS\Application\ApplicationHelper;
use Joomla\CMS\Application\CMSWebApplicationInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\String\StringHelper;

class ExtcalendarModel extends AdminModel
{
	protected $text_prefix = 'COM_DPCALENDAR_EXTCALENDAR';

	public function save($data)
	{
		$app = Factory::getApplication();

		// Alter the title for save as copy
		if ($app->getInput()->get('task') == 'save2copy') {
			$title         = StringHelper::increment($data['title']);
			$alias         = StringHelper::increment($data['alias']);
			$data['title'] = $title;
			$data['alias'] = $alias;
			$data['state'] = 0;
		}

		return parent::save($data);
	}

	public function getTable($type = 'Extcalendar', $prefix = 'Administrator', $config = [])
	{
		return parent::getTable($type, $prefix, $config);
	}

	public function getForm($data = [], $loadData = true)
	{
		// Get the form.
		$form = $this->loadForm('com_dpcalendar.extcalendar', 'extcalendar', ['control' => 'jform', 'load_data' => $loadData]);

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

	protected function preprocessForm(Form $form, $data, $group = 'content')
	{
		$plugin = Factory::getApplication()->getInput()->getWord('dpplugin');

		Factory::getApplication()->getLanguage()->load('plg_dpcalendar_' . $plugin, JPATH_PLUGINS . '/dpcalendar/' . $plugin);
		$form->loadFile(JPATH_PLUGINS . '/dpcalendar/' . $plugin . '/forms/params.xml', false);

		parent::preprocessForm($form, $data, $group);
	}

	protected function loadFormData()
	{
		$app  = Factory::getApplication();
		$data = $app instanceof CMSWebApplicationInterface ? $app->getUserState('com_dpcalendar.edit.extcalendar.data', []) : [];

		if (empty($data)) {
			$data = $this->getItem();
		}

		return $data instanceof BasicTable ? $data->getData() : $data;
	}

	/**
	 * @param ExtcalendarTable $table
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
		if (empty($table->plugin)) {
			$table->plugin = Factory::getApplication()->getInput()->getWord('dpplugin');
		}

		if (empty($table->id)) {
			// Set ordering to the last item if not set
			if (empty($table->ordering)) {
				$this->getDatabase()->setQuery('SELECT MAX(ordering) FROM #__dpcalendar_extcalendars');
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

	public function cleanEventCache(string $plugin): bool
	{
		// Clean the Joomla cache
		$cache = $this->getCacheControllerFactory()->createCacheController('callback', ['defaultgroup' => 'plg_dpcalendar_' . $plugin]);
		if (!$cache->clean()) {
			return false;
		}

		// Clean the DB cache entries from the database
		PluginHelper::importPlugin('dpcalendar');
		$tmp = Factory::getApplication()->triggerEvent('onCalendarsFetch');
		if (!empty($tmp)) {
			$ids = [];
			foreach ($tmp as $calendars) {
				foreach ($calendars as $externalCalendar) {
					if ($externalCalendar->getPluginName() != $plugin) {
						continue;
					}
					$ids[] = $externalCalendar->id;
				}
			}
			if ($ids !== []) {
				// Delete the events
				$this->getDatabase()->setQuery("delete from #__dpcalendar_events where catid in ('" . implode("','", $ids) . "')");
				$this->getDatabase()->execute();

				// Delete the location associations
				$this->getDatabase()->setQuery('delete from #__dpcalendar_events_location where event_id not in (select id from #__dpcalendar_events)');
				$this->getDatabase()->execute();
				$this->getDatabase()->setQuery('delete from #__dpcalendar_events_hosts where event_id not in (select id from #__dpcalendar_events)');
				$this->getDatabase()->execute();

				// Clearing the sync token
				$this->getDatabase()->setQuery("update #__dpcalendar_extcalendars set sync_date = null, sync_token = null where plugin = '" . $plugin . "'");
				$this->getDatabase()->execute();
			}
		}

		return true;
	}
}
