<?php
/**
 * @package   DPCalendar
 * @copyright Copyright (C) 2014 Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace DigitalPeak\Component\DPCalendar\Site\Model;

defined('_JEXEC') or die();

use DigitalPeak\Component\DPCalendar\Administrator\Helper\DPCalendarHelper;
use Joomla\CMS\Application\CMSWebApplicationInterface;
use Joomla\CMS\Application\SiteApplication;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\CMS\User\User;
use Joomla\CMS\User\UserFactoryAwareInterface;
use Joomla\CMS\User\UserFactoryAwareTrait;
use Joomla\Registry\Registry;
use Joomla\String\StringHelper;
use Joomla\Utilities\ArrayHelper;

class ProfileModel extends ListModel implements UserFactoryAwareInterface
{
	use UserFactoryAwareTrait;

	private ?array $items = null;

	public function __construct($config = [], MVCFactoryInterface $factory = null)
	{
		if (empty($config['filter_fields'])) {
			$config['filter_fields'] = [
				'id',
				'a.id',
				'title',
				'a.title',
				'hits',
				'a.hits'
			];
		}

		parent::__construct($config, $factory);
	}

	public function getItems()
	{
		if ($this->items === null) {
			$calendars = parent::getItems();
			if (!is_array($calendars)) {
				return $calendars;
			}

			$myUri = 'principals/' . $this->getCurrentUser()->username;

			$myCalendars       = [];
			$externalCalendars = [];

			/** @var \stdClass $calendar */
			foreach ($calendars as $calendar) {
				if (empty($calendar->calendarcolor)) {
					$calendar->calendarcolor = '3366CC';
				}
				if ($myUri == $calendar->principaluri) {
					$calendar->member_principal_access = null;
				}

				$key   = str_replace('principals/', 'calendars/', (string)$calendar->principaluri) . '/' . $calendar->uri;
				$read  = str_contains($calendar->member_principal_access ?? '', '/calendar-proxy-read');
				$write = str_contains($calendar->member_principal_access ?? '', '/calendar-proxy-write');

				if (!array_key_exists($key, $myCalendars) || !array_key_exists($key, $externalCalendars) || $read) {
					if (empty($calendar->member_principal_access)) {
						$calendar->canEdit = true;
						$myCalendars[$key] = $calendar;
					} else {
						$calendar->canEdit       = $write;
						$externalCalendars[$key] = $calendar;
					}
				}

				if (empty($calendar->member_principal_access)) {
					continue;
				}

				if (!$write) {
					continue;
				}

				$externalCalendars[$key]->canEdit = $write;
			}
			$this->items = array_merge($myCalendars, $externalCalendars);
		}

		return $this->items;
	}

	protected function getListQuery()
	{
		$user = $this->getCurrentUser();

		$db    = $this->getDatabase();
		$query = $db->getQuery(true);

		$query->select('c.*,mainp.uri as member_principal_uri, mainp.displayname as member_principal_name, mp.linkuri member_principal_access');
		$query->from('#__dpcalendar_caldav_calendarinstances c');
		$query->join('left outer', "#__dpcalendar_caldav_principals mainp on mainp.uri = c.principaluri");
		$query->join(
			'left outer',
			"(select memberp.external_id member_external_id, linkp.uri linkuri from #__dpcalendar_caldav_principals memberp
				inner join #__dpcalendar_caldav_groupmembers m on memberp.id = m.member_id
				inner join #__dpcalendar_caldav_principals linkp on m.principal_id = linkp.id) as mp
				on mp.linkuri LIKE CONCAT(c.principaluri, '/%')"
		);

		$query->where('(mainp.external_id = ' . (int)$user->id . ' or mp.member_external_id = ' . (int)$user->id . ')');

		$search = $this->getState('filter.search');
		if (!empty($search)) {
			if (stripos((string)$search, 'id:') === 0) {
				$query->where('c.id = ' . (int)substr((string)$search, 3));
			} else {
				$search = $db->quote('%' . $db->escape((string)StringHelper::strtolower($search), true) . '%');
				$query->where('(LOWER(c.displayname) LIKE ' . $search . ' OR LOWER(c.description) LIKE ' . $search . ')');
			}
		}

		$query->order($db->escape($this->getState('list.ordering', 'c.displayname')) . ' ' . $db->escape($this->getState('list.direction', 'ASC')));

		// Echo str_replace('#__', 'a_', $query); die();
		return $query;
	}

	protected function populateState($ordering = null, $direction = null)
	{
		$app    = Factory::getApplication();
		$params = $app instanceof SiteApplication ? $app->getParams() : ComponentHelper::getParams('com_dpcalendar');

		if ($app->getInput()->getInt('limit', 0) === 0 && $app instanceof CMSWebApplicationInterface) {
			$limit = $app->getUserStateFromRequest('global.list.limit', 'limit', $app->get('list_limit'));
			$this->setState('list.limit', $limit);
		} else {
			$this->setState('list.limit', $app->getInput()->getInt('limit', 0));
		}

		$limitstart = $app->getInput()->getInt('limitstart', 0);
		$this->setState('list.start', $limitstart);

		$orderCol = $app->getInput()->getCmd('filter_order', 'c.displayname');
		if (!in_array($orderCol, $this->filter_fields)) {
			$orderCol = 'c.displayname';
		}
		$this->setState('list.ordering', $orderCol);

		$listOrder = $app->getInput()->getCmd('filter_order_Dir', 'ASC');
		if (!in_array(strtoupper((string)$listOrder), ['ASC', 'DESC', ''])) {
			$listOrder = 'ASC';
		}
		$this->setState('list.direction', $listOrder);

		$this->setState('filter.search', $app->getInput()->getString('filter-search', ''));

		$this->setState('params', $params);
	}

	public function getReadMembers(): array
	{
		$user = $this->getCurrentUser();

		$db    = $this->getDatabase();
		$query = $db->getQuery(true);

		$query->select('p.id AS value, p.displayname AS text');
		$query->from('#__dpcalendar_caldav_principals AS p');
		$query->join('right', '#__dpcalendar_caldav_groupmembers m on p.id = m.member_id');
		$query->where(
			'm.principal_id = (select id from #__dpcalendar_caldav_principals where uri = ' . $db->quote(
				'principals/' . $user->username . '/calendar-proxy-read'
			) . ')'
		);

		$db->setQuery($query);

		return $db->loadObjectList();
	}

	public function getWriteMembers(): array
	{
		$user = $this->getCurrentUser();

		$db    = $this->getDatabase();
		$query = $db->getQuery(true);

		$query->select('p.id AS value, p.displayname AS text');
		$query->from('#__dpcalendar_caldav_principals AS p');
		$query->join('right', '#__dpcalendar_caldav_groupmembers m on p.id = m.member_id');
		$query->where(
			'm.principal_id = (select id from #__dpcalendar_caldav_principals where uri = ' . $db->quote(
				'principals/' . $user->username . '/calendar-proxy-write'
			) . ')'
		);

		$db->setQuery($query);

		return $db->loadObjectList();
	}

	public function getUsers(): array
	{
		$user = $this->getCurrentUser();

		$db    = $this->getDatabase();
		$query = $db->getQuery(true);

		$query = $query->select('id')->from('#__users')->where('block = 0')->where('id != ' . (int)$user->id);

		$db->setQuery($query);
		$userIds = array_map(static fn ($data): mixed => $data['id'], $db->loadAssocList());
		$userIds = ArrayHelper::toInteger($userIds);

		$query = $db->getQuery(true);
		$query->select('p.id AS value, p.displayname AS text');
		$query->from('#__dpcalendar_caldav_principals AS p');
		$query->where('p.external_id in (' . implode(',', $userIds) . ')');
		$query->where('p.uri not like ' . $db->quote('%calendar-proxy-read%'));
		$query->where('p.uri not like ' . $db->quote('%calendar-proxy-write%'));

		$db->setQuery($query);

		return $db->loadObjectList();
	}

	public function getEvents(): array
	{
		$calendars = $this->getItems();
		$ids       = [];
		foreach ($calendars as $calendar) {
			$ids['cd-' . $calendar->id] = 'cd-' . $calendar->id;
		}

		$model = $this->bootComponent('dpcalendar')->getMVCFactory()->createModel('Calendar', 'Site');
		$model->getState();
		$model->setState('filter.parentIds', ['root']);
		foreach ($model->getItems() as $calendar) {
			$ids[$calendar->id] = $calendar->id;
		}

		$model = $this->bootComponent('dpcalendar')->getMVCFactory()->createModel('Events', 'Site', ['ignore_request' => true]);
		$model->getState();
		$model->setState('category.id', $ids);
		$model->setState('category.recursive', true);
		$model->setState('list.limit', 5);
		$model->setState('list.start-date', DPCalendarHelper::getDate()->format('U'));
		$model->setState('list.ordering', 'start_date');
		$model->setState('filter.author', -1);
		$model->setState('filter.expand', 1);
		$model->setState('filter.state', [0, 1, 3]);

		return $model->getItems();
	}

	public function setUsers(array $users, string $type): void
	{
		$db = $this->getDatabase();

		$query = 'delete from #__dpcalendar_caldav_groupmembers
					where principal_id = (select id from #__dpcalendar_caldav_principals
					where uri=' . $db->quote('principals/' . $this->getCurrentUser()->username . '/calendar-proxy-' . $type) . ')';
		$db->setQuery($query);
		$db->execute();
		foreach ($users as $user) {
			if (!$user) {
				continue;
			}

			$query = 'insert into #__dpcalendar_caldav_groupmembers (member_id, principal_id)
					select ' . (int)$user . ' as member_id, id as principal_id from #__dpcalendar_caldav_principals
							where uri=' . $db->quote('principals/' . $this->getCurrentUser()->username . '/calendar-proxy-' . $type);
			$db->setQuery($query);
			$db->execute();
		}
	}

	public function getUserForToken(string $token): ?User
	{
		$db = $this->getDatabase();

		$query = $db->getQuery(true);
		$query->select('id, params')->from('#__users')->where('params like ' . $db->quote('%' . $token . '%'));
		$db->setQuery($query);

		$user = $db->loadAssoc();

		if (!array_key_exists('id', $user)) {
			return null;
		}

		$user       = $this->getUserFactory()->loadUserById($user['id']);
		$userParams = new Registry($user->params);

		if ($userParams->get('token') !== $token) {
			return null;
		}

		return $user;
	}
}
