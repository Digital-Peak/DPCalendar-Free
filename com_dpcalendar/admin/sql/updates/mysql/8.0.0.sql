CREATE TABLE IF NOT EXISTS `#__dpcalendar_coupons` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(250) NOT NULL DEFAULT '',
  `code` varchar(255) NOT NULL DEFAULT '',
  `value` int(11) NOT NULL DEFAULT '0',
  `type` varchar(250) NOT NULL DEFAULT 'percentage',
  `calendars` text,
  `users` text,
  `emails` text,
  `limit` int(11) DEFAULT NULL,
  `state` tinyint(1) NOT NULL DEFAULT '0',
  `checked_out` int(11) NOT NULL DEFAULT '0',
  `checked_out_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `access` int(11) NOT NULL DEFAULT '1',
  `ordering` int(11) NOT NULL DEFAULT '0',
  `params` text,
  `created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `created_by` int(10) unsigned NOT NULL DEFAULT '0',
  `modified` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `modified_by` int(10) unsigned NOT NULL DEFAULT '0',
  `publish_up` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `publish_down` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  KEY `idx_checkout` (`checked_out`),
  KEY `idx_state` (`state`),
  KEY `idx_createdby` (`created_by`)
) DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

ALTER TABLE `#__dpcalendar_bookings` ADD `coupon_id` int(11) AFTER `tax_rate`;
ALTER TABLE `#__dpcalendar_bookings` ADD `coupon_rate` DECIMAL(10, 2) NOT NULL DEFAULT '0.00' AFTER `coupon_id`;

UPDATE `#__dpcalendar_bookings` SET `state` = 0 WHERE `state` = 2;
UPDATE `#__dpcalendar_tickets` SET `state` = 0 WHERE `state` = 2;

ALTER TABLE `#__dpcalendar_tickets` DROP `seat`;

ALTER TABLE `#__dpcalendar_events` DROP `date`;
ALTER TABLE `#__dpcalendar_events` CHANGE `plugintype` `payment_provider` TEXT DEFAULT NULL;
UPDATE `#__dpcalendar_events` SET `payment_provider` = null;
UPDATE `#__dpcalendar_bookings` SET `processor`= CONCAT(processor, '-1') where processor is not null and processor not like '';

ALTER TABLE `#__dpcalendar_locations` DROP `date`;
UPDATE `#__dpcalendar_locations` SET `country` = '0';
ALTER TABLE `#__dpcalendar_locations` CHANGE `country` `country` INT(11) NOT NULL DEFAULT '0';

INSERT INTO `#__content_types` (`type_title`, `type_alias`, `table`, `rules`, `field_mappings`, `router`, `content_history_options`) VALUES
('Location', 'com_dpcalendar.location', '{"special":{"dbtable":"#__dpcalendar_locations","key":"id","type":"Location","prefix":"Administrator","config":"array()"},"common":{"dbtable":"#__ucm_content","key":"ucm_id","type":"Location","prefix":"Administrator","config":"array()"}}', '', '{"common":{"core_content_item_id":"id","core_title":"title","core_state":"state","core_alias":"alias","core_created_time":"created","core_modified_time":"modified","core_body":"description","core_publish_up":"publish_up","core_publish_down":"publish_down","core_access":"access", "core_params":"attribs","core_featured":"featured", "core_metadata":"metadata", "core_language":"language", "core_metakey":"metakey", "core_metadesc":"metadesc"}, "special":{}}', '\DigitalPeak\Component\DPCalendar\Site\Helper\RouteHelper::getLocationRoute', '{"formFile":"administrator\\/components\\/com_dpcalendar\\/models\\/forms\\/location.xml", "hideFields":["checked_out"],"ignoreChanges":["modified_by", "modified", "checked_out", "checked_out_time", "hits"],"convertToInt":["publish_up", "publish_down", "featured"],"displayLookup":[{"sourceColumn":"created_by","targetTable":"#__users","targetColumn":"id","displayColumn":"name"},{"sourceColumn":"access","targetTable":"#__viewlevels","targetColumn":"id","displayColumn":"title"},{"sourceColumn":"modified_by","targetTable":"#__users","targetColumn":"id","displayColumn":"name"} ]}');
