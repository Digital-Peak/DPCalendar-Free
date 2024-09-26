ALTER TABLE `#__dpcalendar_events` ADD `events_discount` TEXT NULL AFTER `user_discount`;
ALTER TABLE `#__dpcalendar_events` ADD `tickets_discount` TEXT NULL AFTER `events_discount`;
ALTER TABLE `#__dpcalendar_bookings` ADD `events_discount` DECIMAL(10, 5) DEFAULT NULL AFTER `coupon_rate`;
ALTER TABLE `#__dpcalendar_bookings` ADD `tickets_discount` DECIMAL(10, 5) DEFAULT NULL AFTER `events_discount`;
