ALTER TABLE `#__dpcalendar_locations` CHANGE `room` `rooms` TEXT NULL;
ALTER TABLE `#__dpcalendar_events` ADD `rooms` TEXT NULL AFTER `cancelurl` ;
