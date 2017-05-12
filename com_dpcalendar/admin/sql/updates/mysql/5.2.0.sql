ALTER TABLE `#__dpcalendar_events` CHANGE `catid` `catid` varchar(255) NOT NULL DEFAULT '0' AFTER `id`;
ALTER TABLE `#__dpcalendar_extcalendars` ADD COLUMN `sync_token` varchar(255);
ALTER TABLE `#__dpcalendar_extcalendars` ADD COLUMN `sync_date` datetime;
ALTER TABLE `#__dpcalendar_extcalendars` ADD COLUMN `color_force` tinyint(1) NOT NULL DEFAULT '0' AFTER `color`;

