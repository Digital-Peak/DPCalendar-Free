ALTER TABLE `#__dpcalendar_events` ADD `booking_cancel_closing_date` VARCHAR(255) NULL DEFAULT NULL AFTER `booking_closing_date`;

UPDATE `#__dpcalendar_bookings` SET `invoice` = 0;
UPDATE `#__dpcalendar_bookings` SET `invoice` = 1 WHERE `processor` LIKE 'manual-%';
ALTER TABLE `#__dpcalendar_bookings` CHANGE `invoice` `invoice` TINYINT NOT NULL DEFAULT '0';
