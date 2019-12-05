ALTER TABLE `#__dpcalendar_events` ADD `booking_series` tinyint(3) unsigned NOT NULL DEFAULT '0' AFTER `max_tickets`;
ALTER TABLE `#__dpcalendar_locations` ADD `color` varchar(250) NOT NULL DEFAULT '' AFTER `description`;
