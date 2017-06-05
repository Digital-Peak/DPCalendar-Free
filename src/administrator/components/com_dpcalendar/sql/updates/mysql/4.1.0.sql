ALTER TABLE `#__dpcalendar_locations` CHANGE `latitude` `latitude` DECIMAL( 20, 15 ) NULL DEFAULT  '0.0';
ALTER TABLE `#__dpcalendar_locations` CHANGE `longitude` `longitude` DECIMAL( 20, 15 ) NULL DEFAULT  '0.0';
ALTER TABLE `#__dpcalendar_extcalendars` ADD COLUMN `access_content` INT( 11 ) NOT NULL DEFAULT  '1';
