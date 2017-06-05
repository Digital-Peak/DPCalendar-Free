UPDATE `#__extensions` SET enabled = 1 WHERE type = 'plugin' AND element = 'dpcalendar';

ALTER TABLE `#__dpcalendar_events` ADD INDEX `idx_start_date` (`start_date`);
ALTER TABLE `#__dpcalendar_events` ADD INDEX `idx_end_date` (`end_date`);
ALTER TABLE `#__dpcalendar_events` ADD COLUMN `latitude` float NULL DEFAULT NULL AFTER `location`;
ALTER TABLE `#__dpcalendar_events` ADD COLUMN `longitude` float NULL DEFAULT NULL AFTER `latitude`;
ALTER TABLE `#__dpcalendar_events` ADD COLUMN `rrule` varchar(255) AFTER `alias`;
