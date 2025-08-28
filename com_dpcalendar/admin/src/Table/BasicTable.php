<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2014 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Component\DPCalendar\Administrator\Table;

\defined('_JEXEC') or die();

use Joomla\CMS\Table\Table;
use Joomla\CMS\User\CurrentUserInterface;
use Joomla\CMS\User\CurrentUserTrait;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Database\DatabaseInterface;

abstract class BasicTable extends Table implements CurrentUserInterface
{
	use CurrentUserTrait;
	use DatabaseAwareTrait;

	/** @var int */
	public $id;

	/** @var int */
	public $state;

	protected string $tableName = '';

	public function __construct(DatabaseInterface $db)
	{
		parent::__construct('#__' . $this->tableName, 'id', $db);
		$this->setDatabase($db);
	}

	public function getData(): array
	{
		$data = [];
		foreach ($this->getFields() as $field) {
			$data[$field->Field] = $this->{$field->Field};
		}

		return $data;
	}
}
