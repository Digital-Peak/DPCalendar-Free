<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2014 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Component\DPCalendar\Administrator\Table;

\defined('_JEXEC') or die();

use DigitalPeak\Component\DPCalendar\Administrator\Helper\DPCalendarHelper;
use Joomla\CMS\Access\Rules;
use Joomla\CMS\Application\ApplicationHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Table\Asset;
use Joomla\CMS\Table\Table;
use Joomla\Registry\Registry;
use Joomla\String\StringHelper;

class ExtcalendarTable extends BasicTable
{
	/** @var string */
	public $params;

	/** @var ?string */
	public $modified;

	/** @var int */
	public $modified_by;

	/** @var ?string */
	public $created;

	/** @var int */
	public $created_by;

	/** @var string */
	public $alias;

	/** @var string */
	public $_tbl_key;

	/** @var string */
	public $title;

	/** @var string */
	public $language;

	/** @var ?string */
	public $publish_down;

	/** @var ?string */
	public $publish_up;

	/** @var ?string */
	public $sync_date;

	/** @var ?string */
	public $sync_token;

	/** @var ?string */
	public $plugin;

	/** @var string */
	public $metakey;

	/** @var string */
	public $color;

	/** @var int */
	public $version;

	/** @var int */
	public $ordering;

	protected string $tableName = 'dpcalendar_extcalendars';
	protected $_columnAlias     = ['published' => 'state'];

	public function delete($pk = null)
	{
		$success = parent::delete($pk);
		if ($success) {
			// Load the DPCalendar plugins to delete cached events
			PluginHelper::importPlugin('dpcalendar');
			Factory::getApplication()->triggerEvent('onCalendarAfterDelete', [$this]);
		}

		return $success;
	}

	public function bind($data, $ignore = '')
	{
		$data = \is_object($data) ? get_object_vars($data) : $data;

		if (isset($data['params']) && \is_array($data['params'])) {
			$registry = new Registry();
			$registry->loadArray($data['params']);
			$data['params'] = (string)$registry;
		}

		// Bind the rules.
		if (isset($data['rules']) && \is_array($data['rules'])) {
			$rules = new Rules($data['rules']);
			$this->setRules($rules);
		}

		return parent::bind($data, $ignore);
	}

	public function load($keys = null, $reset = true)
	{
		$return = parent::load($keys, $reset);

		// Check the password
		if ($this->params) {
			$params = new Registry($this->params);
			if ($pw = $params->get('password')) {
				$params->set('password', DPCalendarHelper::deobfuscate($pw));
			}
			$this->params = $params->toString();
		}

		return $return;
	}

	public function store($updateNulls = false)
	{
		$date = DPCalendarHelper::getDate();
		$user = $this->getCurrentUser();
		if ($this->id !== 0) {
			// Existing item
			$this->modified    = $date->toSql();
			$this->modified_by = $user->id;
		} else {
			if ((int)$this->created === 0) {
				$this->created = $date->toSql();
			}
			if (empty($this->created_by)) {
				$this->created_by = $user->id;
			}
		}

		// Verify that the alias is unique
		$table = new self($this->getDatabase());
		if ($table->load(['alias' => $this->alias]) && ($table->id != $this->id || $this->id == 0)) {
			$this->alias = StringHelper::increment($this->alias);
		}

		// Obfuscate the password
		if (!empty($this->params)) {
			$params = new Registry($this->params);

			if ($pw = $params->get('password')) {
				$params->set('password', DPCalendarHelper::obfuscate($pw));
			}
			$this->params = $params->toString();
		}

		// Attempt to store the user data.
		$success = parent::store($updateNulls);

		if ($success) {
			// Restore the params
			$params = new Registry($this->params);
			if ($pw = $params->get('password')) {
				$params->set('password', DPCalendarHelper::deobfuscate($pw));
			}
			$this->params = $params->toString();
		}

		return $success;
	}

	protected function _getAssetName(): string
	{
		$k = $this->_tbl_key;

		return 'com_dpcalendar.extcalendar.' . (int)$this->$k;
	}

	protected function _getAssetParentId(?Table $table = null, $id = null)
	{
		$asset = new Asset($this->getDatabase());
		$asset->loadByName('com_dpcalendar');

		return $asset->id;
	}

	public function check(): bool
	{
		// Check for valid name
		if (trim($this->title) === '') {
			throw new \Exception(Text::sprintf('COM_DPCALENDAR_EXTCALENDAR_ERR_TABLES_NAME', ''));
		}

		// Check for existing name
		$query = 'SELECT id FROM #__dpcalendar_extcalendars WHERE title = ' . $this->getDatabase()->Quote($this->title);
		$this->getDatabase()->setQuery($query);

		$xid = (int)$this->getDatabase()->loadResult();
		if ($xid && $xid != (int)$this->id) {
			throw new \Exception(Text::sprintf('COM_DPCALENDAR_EXTCALENDAR_ERR_TABLES_NAME', htmlspecialchars($this->title)));
		}

		if (empty($this->alias)) {
			$this->alias = $this->title;
		}
		$this->alias = ApplicationHelper::stringURLSafe($this->alias);
		if (trim(str_replace('-', '', $this->alias)) === '') {
			$this->alias = DPCalendarHelper::getDate()->format("Y-m-d-H-i-s");
		}

		if (empty($this->language)) {
			$this->language = '*';
		}

		// Check the publish down date is not earlier than publish up.
		if ($this->publish_down && $this->publish_down < $this->publish_up) {
			throw new \Exception(Text::_('JGLOBAL_START_PUBLISH_AFTER_FINISH'));
		}

		if (empty($this->created) || $this->created === $this->getDatabase()->getNullDate()) {
			$this->created = null;
		}
		if (empty($this->modified) || $this->modified === $this->getDatabase()->getNullDate()) {
			$this->modified = null;
		}
		if (empty($this->publish_up) || $this->publish_up === $this->getDatabase()->getNullDate()) {
			$this->publish_up = null;
		}
		if (empty($this->publish_down) || $this->publish_down === $this->getDatabase()->getNullDate()) {
			$this->publish_down = null;
		}
		if (empty($this->sync_date) || $this->sync_date === $this->getDatabase()->getNullDate()) {
			$this->sync_date = null;
		}

		// Clean up keywords -- eliminate extra spaces between phrases
		// and cr (\r) and lf (\n) characters from string
		if (!empty($this->metakey)) {
			$bad_characters = ["\n", "\r", '"', "<", ">"];

			$after_clean = utf8_ireplace($bad_characters, "", $this->metakey);
			$keys        = explode(',', $after_clean);
			$clean_keys  = [];
			foreach ($keys as $key) {
				if (trim($key) === '') {
					continue;
				}
				if (trim($key) === '0') {
					continue;
				}
				$clean_keys[] = trim($key);
			}
			$this->metakey = implode(", ", $clean_keys);
		}

		$this->color = str_replace('#', '', (string)$this->color);

		return true;
	}
}
