ALTER TABLE `#__dpcalendar_events` CHANGE `catid` `catid` VARCHAR(255) NOT NULL DEFAULT '0';
ALTER TABLE `#__dpcalendar_events` CHANGE `xreference` `xreference` VARCHAR(255) NULL;
ALTER TABLE `#__dpcalendar_locations` CHANGE `xreference` `xreference` VARCHAR(255) NULL;
ALTER TABLE `#__dpcalendar_bookings` CHANGE `uid` `uid` VARCHAR(255) NOT NULL DEFAULT '';
ALTER TABLE `#__dpcalendar_tickets` CHANGE `uid` `uid` VARCHAR(255) NOT NULL DEFAULT '';
ALTER TABLE `#__dpcalendar_extcalendars` CHANGE `plugin` `plugin` VARCHAR(255) NOT NULL DEFAULT '';
