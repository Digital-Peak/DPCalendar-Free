<?php
/**
 * @package    DPCalendar
 * @author     Digital Peak http://www.digital-peak.com
 * @copyright  Copyright (C) 2007 - 2018 Digital Peak. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 */
defined('_JEXEC') or die();

JLoader::import('joomla.application.component.modellist');

class DPCalendarModelCpanel extends JModelLegacy
{

	public function refreshUpdateSite ()
	{
		if (DPCalendarHelper::isFree())
		{
			return;
		}

		JLoader::import('joomla.application.component.helper');
		$params = JComponentHelper::getParams('com_dpcalendar');

		if (version_compare(JVERSION, '3.0', 'ge'))
		{
			$dlid = $params->get('downloadid', '');
		}
		else
		{
			$dlid = $params->getValue('downloadid', '');
		}
		$dlid = trim($dlid);

		$extra_query = null;

		// If I have a valid Download ID I will need to use a non-blank
		// extra_query in Joomla! 3.2+
		if (preg_match('/^([0-9]{1,}:)?[0-9a-f]{32}$/i', $dlid))
		{
			$extra_query = 'dlid=' . $dlid;
		}

		// Create the update site definition we want to store to the database
		$update_site = array(
				'enabled' => 1,
				'last_check_timestamp' => 0,
				'extra_query' => $extra_query
		);

		if (version_compare(JVERSION, '3.0.0', 'lt'))
		{
			unset($update_site['extra_query']);
		}

		$db = $this->getDbo();

		// Get the extension ID to ourselves
		$query = $db->getQuery(true)
			->select($db->qn('extension_id'))
			->from($db->qn('#__extensions'))
			->where($db->qn('type') . ' = ' . $db->q('package'))
			->where($db->qn('element') . ' = ' . $db->q('pkg_dpcalendar'));
		$db->setQuery($query);

		$extension_id = $db->loadResult();

		if (empty($extension_id))
		{
			return;
		}

		// Get the update sites for our extension
		$query = $db->getQuery(true)
			->select($db->qn('update_site_id'))
			->from($db->qn('#__update_sites_extensions'))
			->where($db->qn('extension_id') . ' = ' . $db->q($extension_id));
		$db->setQuery($query);

		$updateSiteIDs = $db->loadColumn(0);
		if (count($updateSiteIDs))
		{
			// Loop through all update sites
			foreach ($updateSiteIDs as $id)
			{
				$query = $db->getQuery(true)
					->select('*')
					->from($db->qn('#__update_sites'))
					->where($db->qn('update_site_id') . ' = ' . $db->q($id));
				$db->setQuery($query);
				$aSite = $db->loadObject();

				// Do we have the extra_query property (J 3.2+) and does it
				// match?
				if (property_exists($aSite, 'extra_query'))
				{
					if ($aSite->extra_query == $update_site['extra_query'])
					{
						continue;
					}
				}
				else
				{
					// Joomla! 3.1 or earlier. Updates may or may not work.
					continue;
				}

				$update_site['update_site_id'] = $id;
				$newSite = (object) $update_site;
				$db->updateObject('#__update_sites', $newSite, 'update_site_id', true);
			}
		}
	}
}
