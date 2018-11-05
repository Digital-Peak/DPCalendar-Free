alter table `#__dpcalendar_events` CHANGE `images` `images` text;
alter table `#__dpcalendar_events` CHANGE `description` `description` text;
alter table `#__dpcalendar_events` CHANGE `price` `price` text;
alter table `#__dpcalendar_events` CHANGE `earlybird` `earlybird` text;
alter table `#__dpcalendar_events` CHANGE `user_discount` `user_discount` text;
alter table `#__dpcalendar_events` CHANGE `booking_information` `booking_information` text;
alter table `#__dpcalendar_events` CHANGE `booking_options` `booking_options` text;
alter table `#__dpcalendar_events` CHANGE `ordertext` `ordertext` text;
alter table `#__dpcalendar_events` CHANGE `canceltext` `canceltext` text;
alter table `#__dpcalendar_events` CHANGE `rooms` `rooms` text;
alter table `#__dpcalendar_events` CHANGE `params` `params` text;
alter table `#__dpcalendar_events` CHANGE `metakey` `metakey` text;
alter table `#__dpcalendar_events` CHANGE `metadesc` `metadesc` text;
alter table `#__dpcalendar_events` CHANGE `metadata` `metadata` text;
alter table `#__dpcalendar_events` CHANGE `plugintype` `plugintype` text;

alter table `#__dpcalendar_locations` CHANGE `rooms` `rooms` text;
alter table `#__dpcalendar_locations` CHANGE `description` `description` text;
alter table `#__dpcalendar_locations` CHANGE `params` `params` text;

alter table `#__dpcalendar_bookings` CHANGE `raw_data` `raw_data` text;

alter table `#__dpcalendar_extcalendars` CHANGE `description` `description` text;
alter table `#__dpcalendar_extcalendars` CHANGE `params` `params` text;
