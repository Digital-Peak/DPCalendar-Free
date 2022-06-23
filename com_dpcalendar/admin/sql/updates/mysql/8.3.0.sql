CREATE TABLE IF NOT EXISTS `#__dpcalendar_events_hosts` (
  `event_id` int NOT NULL DEFAULT '0',
  `user_id` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`event_id`,`user_id`)
) DEFAULT CHARSET=utf8;

ALTER TABLE `#__dpcalendar_locations` ADD `xreference` VARCHAR(255) NULL;
ALTER TABLE `#__dpcalendar_locations` ADD INDEX `idx_xreference` (`xreference`);

-- Set xreference for locations with no events
UPDATE `#__dpcalendar_locations` SET `xreference`= `title` WHERE id NOT IN (SELECT location_id FROM #__dpcalendar_events_location);
ALTER TABLE `#__dpcalendar_bookings` ADD `token` VARCHAR(255) NULL DEFAULT NULL AFTER `raw_data`;
