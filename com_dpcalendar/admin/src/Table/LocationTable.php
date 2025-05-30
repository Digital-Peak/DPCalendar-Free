<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2014 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Component\DPCalendar\Administrator\Table;

\defined('_JEXEC') or die();

use DigitalPeak\Component\DPCalendar\Administrator\Helper\DPCalendarHelper;
use Joomla\CMS\Application\ApplicationHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Tag\TaggableTableInterface;
use Joomla\CMS\Tag\TaggableTableTrait;
use Joomla\Registry\Registry;

class LocationTable extends BasicTable implements TaggableTableInterface
{
	use TaggableTableTrait;

	/** @var string */
	public $typeAlias = 'com_dpcalendar.location';

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
	public $title;

	/** @var ?string */
	public $publish_down;

	/** @var ?string */
	public $publish_up;

	/** @var string */
	public $metakey;

	/** @var int */
	public $country;

	/** @var int */
	public $checked_out;

	/** @var int */
	public $version;

	/** @var ?string */
	public $checked_out_time;

	/** @var string */
	public $color;

	/** @var float */
	public $latitude;

	/** @var float */
	public $longitude;

	/** @var int */
	public $ordering;

	protected string $tableName = 'dpcalendar_locations';
	protected $_columnAlias     = ['published' => 'state'];

	public function bind($data, $ignore = '')
	{
		$data = \is_object($data) ? get_object_vars($data) : $data;

		if (isset($data['params']) && \is_array($data['params'])) {
			$registry = new Registry();
			$registry->loadArray($data['params']);
			$data['params'] = (string)$registry;
		}

		if (isset($data['metadata']) && \is_array($data['metadata'])) {
			$registry = new Registry();
			$registry->loadArray($data['metadata']);
			$data['metadata'] = (string)$registry;
		}

		if (isset($data['images']) && \is_array($data['images'])) {
			$registry = new Registry();
			$registry->loadArray($data['images']);
			$data['images'] = (string)$registry;
		}

		if (isset($data['rooms']) && \is_array($data['rooms'])) {
			$registry = new Registry();
			$registry->loadArray($data['rooms']);
			$data['rooms'] = (string)$registry;
		}

		return parent::bind($data, $ignore);
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
		$table = new self($this->getDbo());
		if ($table->load(['alias' => $this->alias]) && ($table->id != $this->id || $this->id == 0)) {
			throw new \Exception(Text::_('COM_DPCALENDAR_ERROR_UNIQUE_ALIAS_LOCATION') . ': ' . $table->alias);
		}

		// Attempt to store the user data.
		return parent::store($updateNulls);
	}

	public function check(): bool
	{
		// Check for valid name
		if (trim($this->title) === '') {
			throw new \Exception(Text::_('COM_DPCALENDAR_LOCATION_ERR_TABLES_TITLE'));
		}

		// Check for existing name
		$query = 'SELECT id FROM #__dpcalendar_locations WHERE title = ' . $this->getDatabase()->Quote($this->title);
		$this->getDatabase()->setQuery($query);

		$xid = (int)$this->getDatabase()->loadResult();
		if ($xid && $xid != (int)$this->id) {
			throw new \Exception(Text::_('COM_DPCALENDAR_LOCATION_ERR_TABLES_NAME'));
		}

		if (empty($this->alias)) {
			$this->alias = $this->title;
		}

		$this->alias = ApplicationHelper::stringURLSafe($this->alias);
		if (trim(str_replace('-', '', $this->alias)) === '') {
			$this->alias = DPCalendarHelper::getDate()->format("Y-m-d-H-i-s");
		}

		// Check the publish down date is not earlier than publish up.
		if ($this->publish_down && $this->publish_down < $this->publish_up) {
			throw new \Exception(Text::_('JGLOBAL_START_PUBLISH_AFTER_FINISH'));
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

		$this->country     = (int)$this->country;
		$this->checked_out = (int)$this->checked_out;
		$this->version     = (int)$this->version;

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
		if (empty($this->checked_out_time) || $this->checked_out_time === $this->getDatabase()->getNullDate()) {
			$this->checked_out_time = null;
		}

		if (empty($this->color)) {
			$this->color = Factory::getApplication()->bootComponent('dpcalendar')->getMVCFactory()->createModel('Geo', 'Administrator')->getColor($this);
		}
		$this->color = str_replace('#', '', (string)$this->color);

		return true;
	}

	public function getTypeAlias()
	{
		return $this->typeAlias;
	}
}
