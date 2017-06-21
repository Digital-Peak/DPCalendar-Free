ALTER TABLE `#__dpcalendar_events` CHANGE `metakey` `metakey` TEXT NULL;
ALTER TABLE `#__dpcalendar_events` CHANGE `metadesc` `metadesc` TEXT NULL;
ALTER TABLE `#__dpcalendar_events` CHANGE `metadata` `metadata` TEXT NULL;
ALTER TABLE `#__dpcalendar_events` CHANGE `xreference` `xreference` VARCHAR( 255 ) NULL COMMENT  'A reference to enable linkages to external data sets.';

ALTER TABLE `#__dpcalendar_locations` CHANGE `params` `params` TEXT NULL;