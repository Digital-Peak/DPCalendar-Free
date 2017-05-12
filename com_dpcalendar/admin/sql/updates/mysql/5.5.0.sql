ALTER TABLE `#__dpcalendar_tickets` ADD COLUMN `type` INT( 11 ) NOT NULL DEFAULT '0';
ALTER TABLE `#__dpcalendar_events` ADD COLUMN `earlybird` TEXT NULL DEFAULT NULL after price;
ALTER TABLE `#__dpcalendar_events` ADD COLUMN `booking_information` TEXT NULL DEFAULT NULL after earlybird;
ALTER TABLE `#__dpcalendar_events` CHANGE `price` `price` TEXT NULL DEFAULT NULL;
UPDATE `#__dpcalendar_events` SET `price` = null WHERE `price` = 0;
