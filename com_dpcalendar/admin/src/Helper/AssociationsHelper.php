<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2026 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Component\DPCalendar\Administrator\Helper;

use DigitalPeak\Component\DPCalendar\Administrator\Table\EventTable;
use DigitalPeak\Component\DPCalendar\Site\Helper\AssociationHelper;
use Joomla\CMS\Association\AssociationExtensionHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Associations;
use Joomla\CMS\Table\Table;
use Joomla\Component\Categories\Administrator\Table\CategoryTable;
use Joomla\Database\DatabaseInterface;

\defined('_JEXEC') or die;

class AssociationsHelper extends AssociationExtensionHelper
{
	protected $extension = 'com_dpcalendar';

	protected $itemTypes = ['event', 'category'];

	protected $associationsSupport = true;

	public function getAssociationsForItem($id = 0, $view = null): array
	{
		return AssociationHelper::getAssociations($id, $view);
	}

	public function getAssociations(string $typeName, int $id): array
	{
		$type = $this->getType($typeName);

		$context    = $this->extension . '.item';
		$catidField = 'catid';

		if ($typeName === 'category') {
			$context    = 'com_categories.item';
			$catidField = '';
		}

		return Associations::getAssociations(
			$this->extension,
			$type['tables']['a'],
			$context,
			$id,
			'id',
			'alias',
			$catidField
		);
	}

	public function getItem(string $typeName, int $id): null|Table|bool
	{
		if ($id === 0) {
			return null;
		}

		$table = null;

		switch ($typeName) {
			case 'event':
				$table = new EventTable(Factory::getContainer()->get(DatabaseInterface::class));
				break;

			case 'category':
				$table = new CategoryTable(Factory::getContainer()->get(DatabaseInterface::class));
				break;
		}

		if (!$table instanceof Table) {
			return null;
		}

		$table->load($id);

		return $table;
	}

	public function getType($typeName = ''): array
	{
		$fields  = $this->getFieldsTemplate();
		$tables  = [];
		$joins   = [];
		$support = $this->getSupportTemplate();
		$title   = '';

		if (\in_array($typeName, $this->itemTypes)) {
			switch ($typeName) {
				case 'event':
					$fields['title']    = 'a.title';
					$fields['state']    = 'a.state';
					$fields['ordering'] = 'a.start_date';

					$support['state']     = true;
					$support['acl']       = true;
					$support['checkout']  = true;
					$support['category']  = true;
					$support['save2copy'] = true;

					$tables = ['a' => '#__dpcalendar_events'];

					$title = 'event';
					break;

				case 'category':
					$fields['created_user_id'] = 'a.created_user_id';
					$fields['ordering']        = 'a.lft';
					$fields['level']           = 'a.level';
					$fields['catid']           = '';
					$fields['state']           = 'a.published';

					$support['state']    = true;
					$support['acl']      = true;
					$support['checkout'] = true;
					$support['level']    = true;

					$tables = ['a' => '#__categories'];

					$title = 'category';
					break;
			}
		}

		return [
			'fields'  => $fields,
			'support' => $support,
			'tables'  => $tables,
			'joins'   => $joins,
			'title'   => $title,
		];
	}
}
