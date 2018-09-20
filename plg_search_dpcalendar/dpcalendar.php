<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2018 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */
defined('_JEXEC') or die();

if (!JLoader::import('components.com_dpcalendar.helpers.dpcalendar', JPATH_ADMINISTRATOR)) {
    return;
}

class PlgSearchDPCalendar extends JPlugin
{

    public function __construct(& $subject, $config)
    {
        parent::__construct($subject, $config);
        $this->loadLanguage();
    }

    public function onContentSearchAreas()
    {
        static $areas = array('dpcalendar' => 'PLG_SEARCH_DPCALENDAR_EVENTS');

        return $areas;
    }

    public function onContentSearch($text, $phrase = '', $ordering = '', $areas = null)
    {
        $db         = JFactory::getDbo();
        $serverType = $db->getServerType();
        $app        = JFactory::getApplication();
        $user       = JFactory::getUser();
        $groups     = implode(',', $user->getAuthorisedViewLevels());
        $tag        = JFactory::getLanguage()->getTag();

        JFactory::getLanguage()->load('com_dpcalendar', JPATH_ADMINISTRATOR . '/components/com_dpcalendar');

        $searchText = $text;

        if (is_array($areas) && !array_intersect($areas, array_keys($this->onContentSearchAreas())))
        {
            return array();
        }

        $sContent  = $this->params->get('search_content', 1);
        $sArchived = $this->params->get('search_archived', 1);
        $limit     = $this->params->def('search_limit', 50);

        $nullDate  = $db->getNullDate();
        $date      = JFactory::getDate();
        $now       = $date->toSql();

        $text = trim($text);

        if ($text === '')
        {
            return array();
        }

        switch ($phrase)
        {
            case 'exact':
                $text      = $db->quote('%' . $db->escape($text, true) . '%', false);
                $wheres2   = array();
                $wheres2[] = 'a.title LIKE ' . $text;
                $wheres2[] = 'a.description LIKE ' . $text;
                $wheres2[] = 'a.metakey LIKE ' . $text;
                $wheres2[] = 'a.metadesc LIKE ' . $text;

                $relevance[] = ' CASE WHEN ' . $wheres2[0] . ' THEN 5 ELSE 0 END ';

                // Join over Fields.
                $subQuery = $db->getQuery(true);
                $subQuery->select("cfv.item_id")
                    ->from("#__fields_values AS cfv")
                    ->join('LEFT', '#__fields AS f ON f.id = cfv.field_id')
                    ->where('(f.context IS NULL OR f.context = ' . $db->q('com_dpcalendar.event') . ')')
                    ->where('(f.state IS NULL OR f.state = 1)')
                    ->where('(f.access IS NULL OR f.access IN (' . $groups . '))')
                    ->where('cfv.value LIKE ' . $text);

                // Filter by language.
                if ($app->isClient('site') && JLanguageMultilang::isEnabled())
                {
                    $subQuery->where('(f.language IS NULL OR f.language in (' . $db->quote($tag) . ',' . $db->quote('*') . '))');
                }

                if ($serverType == "mysql")
                {
                    /* This generates a dependent sub-query so do no use in MySQL prior to version 6.0 !
                    * $wheres2[] = 'a.id IN( '. (string) $subQuery.')';
                    */

                    $db->setQuery($subQuery);
                    $fieldids = $db->loadColumn();

                    if (count($fieldids))
                    {
                        $wheres2[] = 'a.id IN(' . implode(",", $fieldids) . ')';
                    }
                }
                else
                {
                    $wheres2[] = $subQuery->castAsChar('a.id') . ' IN( ' . (string) $subQuery . ')';
                }

                $where = '(' . implode(') OR (', $wheres2) . ')';
                break;

            case 'all':
            case 'any':
            default:
                $words = explode(' ', $text);
                $wheres = array();
                $cfwhere = array();

                foreach ($words as $word)
                {
                    $word      = $db->quote('%' . $db->escape($word, true) . '%', false);
                    $wheres2   = array();
                    $wheres2[] = 'LOWER(a.title) LIKE LOWER(' . $word . ')';
                    $wheres2[] = 'LOWER(a.description) LIKE LOWER(' . $word . ')';
                    $wheres2[] = 'LOWER(a.metakey) LIKE LOWER(' . $word . ')';
                    $wheres2[] = 'LOWER(a.metadesc) LIKE LOWER(' . $word . ')';

                    $relevance[] = ' CASE WHEN ' . $wheres2[0] . ' THEN 5 ELSE 0 END ';

                    if ($phrase === 'all')
                    {
                        // Join over Fields.
                        $subQuery = $db->getQuery(true);
                        $subQuery->select("cfv.item_id")
                            ->from("#__fields_values AS cfv")
                            ->join('LEFT', '#__fields AS f ON f.id = cfv.field_id')
                            ->where('(f.context IS NULL OR f.context = ' . $db->q('com_dpcalendar.event') . ')')
                            ->where('(f.state IS NULL OR f.state = 1)')
                            ->where('(f.access IS NULL OR f.access IN (' . $groups . '))')
                            ->where('LOWER(cfv.value) LIKE LOWER(' . $word . ')');

                        // Filter by language.
                        if ($app->isClient('site') && JLanguageMultilang::isEnabled())
                        {
                            $subQuery->where('(f.language IS NULL OR f.language in (' . $db->quote($tag) . ',' . $db->quote('*') . '))');
                        }

                        if ($serverType == "mysql")
                        {
                            $db->setQuery($subQuery);
                            $fieldids = $db->loadColumn();

                            if (count($fieldids))
                            {
                                $wheres2[] = 'a.id IN(' . implode(",", $fieldids) . ')';
                            }
                        }
                        else
                        {
                            $wheres2[] = $subQuery->castAsChar('a.id') . ' IN( ' . (string) $subQuery . ')';
                        }
                    }
                    else
                    {
                        $cfwhere[] = 'LOWER(cfv.value) LIKE LOWER(' . $word . ')';
                    }

                    $wheres[] = implode(' OR ', $wheres2);
                }

                if ($phrase === 'any')
                {
                    // Join over Fields.
                    $subQuery = $db->getQuery(true);
                    $subQuery->select("cfv.item_id")
                        ->from("#__fields_values AS cfv")
                        ->join('LEFT', '#__fields AS f ON f.id = cfv.field_id')
                        ->where('(f.context IS NULL OR f.context = ' . $db->q('com_dpcalendar.event') . ')')
                        ->where('(f.state IS NULL OR f.state = 1)')
                        ->where('(f.access IS NULL OR f.access IN (' . $groups . '))')
                        ->where('(' . implode(($phrase === 'all' ? ') AND (' : ') OR ('), $cfwhere) . ')');

                    // Filter by language.
                    if ($app->isClient('site') && JLanguageMultilang::isEnabled())
                    {
                        $subQuery->where('(f.language IS NULL OR f.language in (' . $db->quote($tag) . ',' . $db->quote('*') . '))');
                    }

                    if ($serverType == "mysql")
                    {
                        $db->setQuery($subQuery);
                        $fieldids = $db->loadColumn();

                        if (count($fieldids))
                        {
                            $wheres[] = 'a.id IN(' . implode(",", $fieldids) . ')';
                        }
                    }
                    else
                    {
                        $wheres[] = $subQuery->castAsChar('a.id') . ' IN( ' . (string) $subQuery . ')';
                    }
                }

                $where = '(' . implode(($phrase === 'all' ? ') AND (' : ') OR ('), $wheres) . ')';
                break;
        }

        switch ($ordering)
        {
            case 'oldest':
                $order = 'a.created ASC';
                break;

            case 'popular':
                $order = 'a.hits DESC';
                break;

            case 'alpha':
                $order = 'a.title ASC';
                break;

            case 'category':
                $order = 'c.title ASC, a.title ASC';
                break;

            case 'newest':
            default:
                $order = 'a.created DESC';
                break;
        }

        $rows = array();
        $query = $db->getQuery(true);

        // Search articles.
        if ($sContent && $limit > 0)
        {
            $query->clear();

            // SQLSRV changes.
            $case_when  = ' CASE WHEN ';
            $case_when .= $query->charLength('a.alias', '!=', '0');
            $case_when .= ' THEN ';
            $a_id       = $query->castAsChar('a.id');
            $case_when .= $query->concatenate(array($a_id, 'a.alias'), ':');
            $case_when .= ' ELSE ';
            $case_when .= $a_id . ' END as slug';

            $case_when1  = ' CASE WHEN ';
            $case_when1 .= $query->charLength('c.alias', '!=', '0');
            $case_when1 .= ' THEN ';
            $c_id        = $query->castAsChar('c.id');
            $case_when1 .= $query->concatenate(array($c_id, 'c.alias'), ':');
            $case_when1 .= ' ELSE ';
            $case_when1 .= $c_id . ' END as catslug';

            if (!empty($relevance))
            {
                $query->select(implode(' + ', $relevance) . ' AS relevance');
                $order = ' relevance DESC, ' . $order;
            }

            $query->select('a.id, a.title AS title, a.metadesc, a.metakey, a.created AS created, a.language, a.catid, a.description AS text')
                ->select('c.title AS section, ' . $case_when . ',' . $case_when1 . ', ' . '\'2\' AS browsernav')
                ->from('#__dpcalendar_events AS a')
                ->join('INNER', '#__categories AS c ON c.id=a.catid')
                ->where(
                    '(' . $where . ') AND a.state=1 AND c.published = 1 AND a.access IN (' . $groups . ') '
                        . 'AND c.access IN (' . $groups . ')'
                        . 'AND (a.publish_up = ' . $db->quote($nullDate) . ' OR a.publish_up <= ' . $db->quote($now) . ') '
                        . 'AND (a.publish_down = ' . $db->quote($nullDate) . ' OR a.publish_down >= ' . $db->quote($now) . ')'
                )
                ->group('a.id, a.title, a.metadesc, a.metakey, a.created, a.language, a.catid, a.description, c.title, a.alias, c.alias, c.id')
                ->order($order);

            // Filter by language.
            if ($app->isClient('site') && JLanguageMultilang::isEnabled())
            {
                $query->where('a.language in (' . $db->quote($tag) . ',' . $db->quote('*') . ')')
                    ->where('c.language in (' . $db->quote($tag) . ',' . $db->quote('*') . ')');
            }

            $db->setQuery($query, 0, $limit);

            try
            {
                $events = $db->loadObjectList();
            }
            catch (RuntimeException $e)
            {
                $events = array();
                JFactory::getApplication()->enqueueMessage(JText::_('JERROR_AN_ERROR_HAS_OCCURRED'), 'error');
            }

            $limit -= count($events);

            if (isset($events))
            {
                foreach ($events as $key => $item)
                {
                    $events[$key]->browsernav = $item->title;
                    $events[$key]->href       = DPCalendarHelperRoute::getEventRoute($item->id, $item->catid);
                }
            }
        }

//            echo '<pre>'; print_r($events); echo '</pre>'; exit;

        return $events;
    }
}
