ALTER TABLE `#__dpcalendar_events` CHANGE `catid` `catid` VARCHAR(191) NOT NULL DEFAULT '0';
ALTER TABLE `#__dpcalendar_events` CHANGE `xreference` `xreference` VARCHAR(191) NULL;
ALTER TABLE `#__dpcalendar_locations` CHANGE `xreference` `xreference` VARCHAR(191) NULL;
ALTER TABLE `#__dpcalendar_bookings` CHANGE `uid` `uid` VARCHAR(191) NOT NULL DEFAULT '';
ALTER TABLE `#__dpcalendar_tickets` CHANGE `uid` `uid` VARCHAR(191) NOT NULL DEFAULT '';
ALTER TABLE `#__dpcalendar_extcalendars` CHANGE `plugin` `plugin` VARCHAR(191) NOT NULL DEFAULT '';
