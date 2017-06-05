ALTER TABLE `#__dpcalendar_events` ADD COLUMN `max_tickets` int(11) NOT NULL DEFAULT '1' AFTER `capacity_used`;
ALTER TABLE `#__dpcalendar_attendees` RENAME TO `#__dpcalendar_bookings`;
ALTER TABLE `#__dpcalendar_bookings` CHANGE `attend_date` `book_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' AFTER `name`;
ALTER TABLE `#__dpcalendar_bookings` ADD COLUMN `uid` varchar(255) AFTER `user_id`;
ALTER TABLE `#__dpcalendar_bookings` ADD INDEX `uid` (`uid`);
ALTER TABLE `#__dpcalendar_bookings` ADD COLUMN `currency` varchar(10) NOT NULL AFTER `price`;
ALTER TABLE `#__dpcalendar_bookings` ADD COLUMN `txn_currency` varchar(10) NOT NULL AFTER `txn_type`;
ALTER TABLE `#__dpcalendar_bookings` ADD COLUMN `raw_data` text NOT NULL;

CREATE TABLE IF NOT EXISTS `#__dpcalendar_tickets` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `booking_id` int(11) NOT NULL,
  `event_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `uid` varchar(255) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `telephone` varchar(255) DEFAULT NULL,
  `country` varchar(255) NOT NULL DEFAULT '',
  `province` varchar(255) NOT NULL DEFAULT '',
  `city` varchar(255) NOT NULL DEFAULT '',
  `zip` varchar(255) NOT NULL DEFAULT '',
  `street` varchar(255) NOT NULL DEFAULT '',
  `number` varchar(255) NOT NULL DEFAULT '',
  `latitude` DECIMAL(12, 8) DEFAULT NULL,
  `longitude` DECIMAL(12, 8) DEFAULT NULL,
  `seat` varchar(255) DEFAULT NULL,
  `remind_time` int(11) NOT NULL,
  `remind_type` tinyint(1) NOT NULL DEFAULT '1',
  `reminder_sent_date` datetime DEFAULT NULL,
  `created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `state` tinyint(1) NOT NULL DEFAULT '0',
  `public` tinyint(1) NOT NULL DEFAULT '1',
  `price` DECIMAL(10, 2) NOT NULL DEFAULT '0.00',
  PRIMARY KEY (`id`),
  KEY `uid` (`uid`),
  KEY `booking_id` (`booking_id`),
  KEY `event_id` (`event_id`),
  KEY `user_id` (`user_id`),
  KEY `state` (`state`),
  KEY `notify` (`reminder_sent_date`, `state`)
) DEFAULT CHARSET=utf8;
