ALTER TABLE `#__dpcalendar_events` ADD `terms` VARCHAR(255) NULL AFTER `cancelurl`;
ALTER TABLE `#__dpcalendar_events` ADD `booking_options` TEXT NULL AFTER `booking_information`;
ALTER TABLE `#__dpcalendar_bookings` ADD `options` VARCHAR(255) NULL AFTER `price`;

UPDATE `#__dpcalendar_events` SET `images`= REPLACE(`images`, 'image1', 'image_full');
UPDATE `#__dpcalendar_events` SET `images`= REPLACE(`images`, 'image2', 'image_intro');
