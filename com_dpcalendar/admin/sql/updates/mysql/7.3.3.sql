UPDATE #__extensions SET enabled = 1 WHERE name = 'plg_dpcalendar_private';
UPDATE #__extensions SET enabled = 1 WHERE name = 'plg_user_dpcalendar';
DELETE FROM #__extensions WHERE name = 'plg_system_dpcalendar';
