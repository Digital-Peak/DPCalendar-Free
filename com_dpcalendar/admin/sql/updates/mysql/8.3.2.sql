CREATE TABLE IF NOT EXISTS `#__dpcalendar_events_hosts` (
  `event_id` int NOT NULL DEFAULT '0',
  `user_id` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`event_id`,`user_id`)
) DEFAULT CHARSET=utf8;
