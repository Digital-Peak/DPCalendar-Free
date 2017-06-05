ALTER TABLE `#__dpcalendar_events` ADD COLUMN  `capacity` int( 11 ) NULL AFTER `hits`;
UPDATE `#__dpcalendar_events` set capacity = 0;
ALTER TABLE `#__dpcalendar_events` ADD COLUMN  `capacity_used` int( 11 ) default 0 AFTER `capacity`;

CREATE TABLE IF NOT EXISTS `#__dpcalendar_attendees` (
 `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
 `event_id` int(11) NOT NULL,
 `user_id` int(11) NOT NULL DEFAULT '0',
 `location_id` int(11) NOT NULL DEFAULT '0',
 `email` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
 `telephone` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
 `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
 `attend_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
 `remind_time` int(11) NOT NULL,
 `remind_type` tinyint(1) NOT NULL DEFAULT '1',
 `reminder_sent_date` datetime DEFAULT NULL,
 `public` tinyint(1) NOT NULL DEFAULT '1',
 `state` tinyint(1) NOT NULL DEFAULT '0',
 PRIMARY KEY (`id`),
 KEY `event_id` (`event_id`)
) DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

ALTER TABLE `#__dpcalendar_events` ADD COLUMN `recurrence_id` varchar(255) DEFAULT NULL AFTER `rrule`;
UPDATE `#__dpcalendar_events` set recurrence_id = DATE_FORMAT(start_date, '%Y%m%dT%H%i%sZ') where original_id > 0;
ALTER TABLE `#__dpcalendar_locations` change `latitude` `latitude` decimal( 9, 6 ) null default null;
