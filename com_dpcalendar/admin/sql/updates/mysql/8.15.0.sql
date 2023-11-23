ALTER TABLE `#__dpcalendar_events` ADD `exdates` text AFTER `rrule`;
ALTER TABLE `#__dpcalendar_coupons` ADD `area` int NOT NULL DEFAULT '1' AFTER `type`;

-- Trim indexed column lengths to support MySQL 5.6
ALTER TABLE `#__dpcalendar_events` CHANGE `catid` `catid` VARCHAR(191) NOT NULL DEFAULT '0';
ALTER TABLE `#__dpcalendar_events` CHANGE `xreference` `xreference` VARCHAR(191) NULL;
ALTER TABLE `#__dpcalendar_locations` CHANGE `xreference` `xreference` VARCHAR(191) NULL;
ALTER TABLE `#__dpcalendar_bookings` CHANGE `uid` `uid` VARCHAR(191) NOT NULL DEFAULT '';
ALTER TABLE `#__dpcalendar_tickets` CHANGE `uid` `uid` VARCHAR(191) NOT NULL DEFAULT '';
ALTER TABLE `#__dpcalendar_extcalendars` CHANGE `plugin` `plugin` VARCHAR(191) NOT NULL DEFAULT '';

-- Change character set of tables
ALTER TABLE `#__dpcalendar_events` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `#__dpcalendar_locations` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `#__dpcalendar_events_location` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `#__dpcalendar_events_hosts` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `#__dpcalendar_bookings` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `#__dpcalendar_tickets` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `#__dpcalendar_coupons` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `#__dpcalendar_extcalendars` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `#__dpcalendar_taxrates` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `#__dpcalendar_countries` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `#__dpcalendar_caldav_calendarobjects` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `#__dpcalendar_caldav_calendars` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `#__dpcalendar_caldav_calendarinstances` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `#__dpcalendar_caldav_calendarchanges` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `#__dpcalendar_caldav_calendarsubscriptions` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `#__dpcalendar_caldav_schedulingobjects` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `#__dpcalendar_caldav_principals` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `#__dpcalendar_caldav_groupmembers` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `#__dpcalendar_caldav_propertystorage` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
