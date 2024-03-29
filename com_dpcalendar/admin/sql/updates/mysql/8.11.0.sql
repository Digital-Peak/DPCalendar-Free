ALTER TABLE `#__dpcalendar_events` CHANGE `start_date` `start_date` DATETIME NULL DEFAULT NULL;
UPDATE `#__dpcalendar_events` SET `start_date` = NULL WHERE `start_date` = '0000-00-00 00:00:00';
ALTER TABLE `#__dpcalendar_events` CHANGE `end_date` `end_date` DATETIME NULL DEFAULT NULL;
UPDATE `#__dpcalendar_events` SET `end_date` = NULL WHERE `end_date` = '0000-00-00 00:00:00';
ALTER TABLE `#__dpcalendar_events` CHANGE `checked_out_time` `checked_out_time` DATETIME NULL DEFAULT NULL;
UPDATE `#__dpcalendar_events` SET `checked_out_time` = NULL WHERE `checked_out_time` = '0000-00-00 00:00:00';
ALTER TABLE `#__dpcalendar_events` CHANGE `created` `created` DATETIME NULL DEFAULT NULL;
UPDATE `#__dpcalendar_events` SET `created` = NULL WHERE `created` = '0000-00-00 00:00:00';
ALTER TABLE `#__dpcalendar_events` CHANGE `modified` `modified` DATETIME NULL DEFAULT NULL;
UPDATE `#__dpcalendar_events` SET `modified` = NULL WHERE `modified` = '0000-00-00 00:00:00';
ALTER TABLE `#__dpcalendar_events` CHANGE `publish_up` `publish_up` DATETIME NULL DEFAULT NULL;
UPDATE `#__dpcalendar_events` SET `publish_up` = NULL WHERE `publish_up` = '0000-00-00 00:00:00';
ALTER TABLE `#__dpcalendar_events` CHANGE `publish_down` `publish_down` DATETIME NULL DEFAULT NULL;
UPDATE `#__dpcalendar_events` SET `publish_down` = NULL WHERE `publish_down` = '0000-00-00 00:00:00';

ALTER TABLE `#__dpcalendar_locations` CHANGE `checked_out_time` `checked_out_time` DATETIME NULL DEFAULT NULL;
UPDATE `#__dpcalendar_locations` SET `checked_out_time` = NULL WHERE `checked_out_time` = '0000-00-00 00:00:00';
ALTER TABLE `#__dpcalendar_locations` CHANGE `created` `created` DATETIME NULL DEFAULT NULL;
UPDATE `#__dpcalendar_locations` SET `created` = NULL WHERE `created` = '0000-00-00 00:00:00';
ALTER TABLE `#__dpcalendar_locations` CHANGE `modified` `modified` DATETIME NULL DEFAULT NULL;
UPDATE `#__dpcalendar_locations` SET `modified` = NULL WHERE `modified` = '0000-00-00 00:00:00';
ALTER TABLE `#__dpcalendar_locations` CHANGE `publish_up` `publish_up` DATETIME NULL DEFAULT NULL;
UPDATE `#__dpcalendar_locations` SET `publish_up` = NULL WHERE `publish_up` = '0000-00-00 00:00:00';
ALTER TABLE `#__dpcalendar_locations` CHANGE `publish_down` `publish_down` DATETIME NULL DEFAULT NULL;
UPDATE `#__dpcalendar_locations` SET `publish_down` = NULL WHERE `publish_down` = '0000-00-00 00:00:00';

ALTER TABLE `#__dpcalendar_bookings` CHANGE `book_date` `book_date` DATETIME NULL DEFAULT NULL;
UPDATE `#__dpcalendar_bookings` SET `book_date` = NULL WHERE `book_date` = '0000-00-00 00:00:00';
ALTER TABLE `#__dpcalendar_tickets` CHANGE `created` `created` DATETIME NULL DEFAULT NULL;
UPDATE `#__dpcalendar_tickets` SET `created` = NULL WHERE `created` = '0000-00-00 00:00:00';

ALTER TABLE `#__dpcalendar_coupons` CHANGE `checked_out_time` `checked_out_time` DATETIME NULL DEFAULT NULL;
UPDATE `#__dpcalendar_coupons` SET `checked_out_time` = NULL WHERE `checked_out_time` = '0000-00-00 00:00:00';
ALTER TABLE `#__dpcalendar_coupons` CHANGE `created` `created` DATETIME NULL DEFAULT NULL;
UPDATE `#__dpcalendar_coupons` SET `created` = NULL WHERE `created` = '0000-00-00 00:00:00';
ALTER TABLE `#__dpcalendar_coupons` CHANGE `modified` `modified` DATETIME NULL DEFAULT NULL;
UPDATE `#__dpcalendar_coupons` SET `modified` = NULL WHERE `modified` = '0000-00-00 00:00:00';
ALTER TABLE `#__dpcalendar_coupons` CHANGE `publish_up` `publish_up` DATETIME NULL DEFAULT NULL;
UPDATE `#__dpcalendar_coupons` SET `publish_up` = NULL WHERE `publish_up` = '0000-00-00 00:00:00';
ALTER TABLE `#__dpcalendar_coupons` CHANGE `publish_down` `publish_down` DATETIME NULL DEFAULT NULL;
UPDATE `#__dpcalendar_coupons` SET `publish_down` = NULL WHERE `publish_down` = '0000-00-00 00:00:00';

ALTER TABLE `#__dpcalendar_extcalendars` CHANGE `created` `created` DATETIME NULL DEFAULT NULL;
UPDATE `#__dpcalendar_extcalendars` SET `created` = NULL WHERE `created` = '0000-00-00 00:00:00';
ALTER TABLE `#__dpcalendar_extcalendars` CHANGE `modified` `modified` DATETIME NULL DEFAULT NULL;
UPDATE `#__dpcalendar_extcalendars` SET `modified` = NULL WHERE `modified` = '0000-00-00 00:00:00';
ALTER TABLE `#__dpcalendar_extcalendars` CHANGE `publish_up` `publish_up` DATETIME NULL DEFAULT NULL;
UPDATE `#__dpcalendar_extcalendars` SET `publish_up` = NULL WHERE `publish_up` = '0000-00-00 00:00:00';
ALTER TABLE `#__dpcalendar_extcalendars` CHANGE `publish_down` `publish_down` DATETIME NULL DEFAULT NULL;
UPDATE `#__dpcalendar_extcalendars` SET `publish_down` = NULL WHERE `publish_down` = '0000-00-00 00:00:00';
ALTER TABLE `#__dpcalendar_extcalendars` CHANGE `sync_date` `sync_date` DATETIME NULL DEFAULT NULL;
UPDATE `#__dpcalendar_extcalendars` SET `sync_date` = NULL WHERE `sync_date` = '0000-00-00 00:00:00';

ALTER TABLE `#__dpcalendar_taxrates` CHANGE `checked_out_time` `checked_out_time` DATETIME NULL DEFAULT NULL;
UPDATE `#__dpcalendar_taxrates` SET `checked_out_time` = NULL WHERE `checked_out_time` = '0000-00-00 00:00:00';
ALTER TABLE `#__dpcalendar_taxrates` CHANGE `created` `created` DATETIME NULL DEFAULT NULL;
UPDATE `#__dpcalendar_taxrates` SET `created` = NULL WHERE `created` = '0000-00-00 00:00:00';
ALTER TABLE `#__dpcalendar_taxrates` CHANGE `modified` `modified` DATETIME NULL DEFAULT NULL;
UPDATE `#__dpcalendar_taxrates` SET `modified` = NULL WHERE `modified` = '0000-00-00 00:00:00';
ALTER TABLE `#__dpcalendar_taxrates` CHANGE `publish_up` `publish_up` DATETIME NULL DEFAULT NULL;
UPDATE `#__dpcalendar_taxrates` SET `publish_up` = NULL WHERE `publish_up` = '0000-00-00 00:00:00';
ALTER TABLE `#__dpcalendar_taxrates` CHANGE `publish_down` `publish_down` DATETIME NULL DEFAULT NULL;
UPDATE `#__dpcalendar_taxrates` SET `publish_down` = NULL WHERE `publish_down` = '0000-00-00 00:00:00';

ALTER TABLE `#__dpcalendar_countries` CHANGE `checked_out_time` `checked_out_time` DATETIME NULL DEFAULT NULL;
UPDATE `#__dpcalendar_countries` SET `checked_out_time` = NULL WHERE `checked_out_time` = '0000-00-00 00:00:00';
ALTER TABLE `#__dpcalendar_countries` CHANGE `created` `created` DATETIME NULL DEFAULT NULL;
UPDATE `#__dpcalendar_countries` SET `created` = NULL WHERE `created` = '0000-00-00 00:00:00';
ALTER TABLE `#__dpcalendar_countries` CHANGE `modified` `modified` DATETIME NULL DEFAULT NULL;
UPDATE `#__dpcalendar_countries` SET `modified` = NULL WHERE `modified` = '0000-00-00 00:00:00';
ALTER TABLE `#__dpcalendar_countries` CHANGE `publish_up` `publish_up` DATETIME NULL DEFAULT NULL;
UPDATE `#__dpcalendar_countries` SET `publish_up` = NULL WHERE `publish_up` = '0000-00-00 00:00:00';
ALTER TABLE `#__dpcalendar_countries` CHANGE `publish_down` `publish_down` DATETIME NULL DEFAULT NULL;
UPDATE `#__dpcalendar_countries` SET `publish_down` = NULL WHERE `publish_down` = '0000-00-00 00:00:00';
