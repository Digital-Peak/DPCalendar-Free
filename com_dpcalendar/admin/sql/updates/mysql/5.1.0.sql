ALTER TABLE `#__dpcalendar_attendees` DROP location_id;
ALTER TABLE `#__dpcalendar_attendees` ADD COLUMN `country` varchar(255) NOT NULL DEFAULT '' AFTER `telephone`;
ALTER TABLE `#__dpcalendar_attendees` ADD COLUMN `province` varchar(255) NOT NULL DEFAULT '' AFTER `country`;
ALTER TABLE `#__dpcalendar_attendees` ADD COLUMN `city` varchar(255) NOT NULL DEFAULT '' AFTER `province`;
ALTER TABLE `#__dpcalendar_attendees` ADD COLUMN `zip` varchar(255) NOT NULL DEFAULT '' AFTER `city`;
ALTER TABLE `#__dpcalendar_attendees` ADD COLUMN `street` varchar(255) NOT NULL DEFAULT '' AFTER `zip`;
ALTER TABLE `#__dpcalendar_attendees` ADD COLUMN `number` varchar(255) NOT NULL DEFAULT '' AFTER `street`;
ALTER TABLE `#__dpcalendar_attendees` ADD COLUMN `latitude` DECIMAL(12, 8) DEFAULT NULL AFTER `number`;
ALTER TABLE `#__dpcalendar_attendees` ADD COLUMN `longitude` DECIMAL(12, 8) DEFAULT NULL AFTER `latitude`;
