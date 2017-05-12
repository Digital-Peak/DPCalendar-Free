ALTER IGNORE TABLE  `#__dpcalendar_attendees` 
ADD COLUMN `transaction_id` VARCHAR( 255 ) NULL DEFAULT NULL ,
ADD COLUMN `price` DECIMAL( 10, 2 ) NOT NULL DEFAULT  '0.00',
ADD COLUMN `processor` VARCHAR( 255 ) DEFAULT NULL,
ADD COLUMN `net_amount` DECIMAL( 10, 2 ) NOT NULL DEFAULT  '0.00',
ADD COLUMN `tax_amount` DECIMAL( 10, 2 ) NOT NULL DEFAULT  '0.00',
ADD COLUMN `gross_amount` DECIMAL( 10, 2 ) NOT NULL DEFAULT  '0.00',
ADD COLUMN `payment_fee` DECIMAL( 10, 2 ) NOT NULL DEFAULT  '0.00',
ADD COLUMN `tax_percent` FLOAT DEFAULT NULL,
ADD COLUMN `txn_type` VARCHAR( 255 ) NOT NULL ,
ADD COLUMN `payer_id` VARCHAR( 255 ) NOT NULL ,
ADD COLUMN `payer_email` VARCHAR( 255 ) NOT NULL;

ALTER IGNORE TABLE `#__dpcalendar_events` ADD COLUMN `price` DECIMAL(10, 2) NOT NULL DEFAULT '0.00' AFTER `capacity_used`;
ALTER IGNORE TABLE `#__dpcalendar_events` ADD COLUMN `tax` TINYINT(1) NOT NULL DEFAULT '0' AFTER `price`;
ALTER IGNORE TABLE `#__dpcalendar_events` ADD COLUMN `ordertext` TEXT NOT NULL AFTER `tax`;
ALTER IGNORE TABLE `#__dpcalendar_events` ADD COLUMN `orderurl` TEXT NOT NULL AFTER `ordertext`;
ALTER IGNORE TABLE `#__dpcalendar_events` ADD COLUMN `canceltext` TEXT NOT NULL AFTER `orderurl`;
ALTER IGNORE TABLE `#__dpcalendar_events` ADD COLUMN `cancelurl` TEXT NOT NULL AFTER `canceltext`;
ALTER IGNORE TABLE `#__dpcalendar_events` ADD COLUMN `plugintype` TEXT NOT NULL;

CREATE TABLE IF NOT EXISTS `#__dpcalendar_extcalendars` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `asset_id` int(10) unsigned NOT NULL DEFAULT '0',
  `title` varchar(255) NOT NULL DEFAULT '',
  `alias` varchar(255) NOT NULL DEFAULT '',
  `plugin` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `color` varchar(250) NOT NULL DEFAULT '',
  `state` tinyint(1) NOT NULL DEFAULT '0',
  `ordering` int(11) NOT NULL DEFAULT '0',
  `params` text NOT NULL,
  `language` char(7) NOT NULL DEFAULT '',
  `created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `created_by` int(10) unsigned NOT NULL DEFAULT '0',
  `created_by_alias` varchar(255) NOT NULL DEFAULT '',
  `version` int(10) unsigned NOT NULL DEFAULT '0',
  `modified` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `modified_by` int(10) unsigned NOT NULL DEFAULT '0',
  `publish_up` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `publish_down` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  KEY `idx_plugin` (`plugin`),
  KEY `idx_state` (`state`),
  KEY `idx_createdby` (`created_by`),
  KEY `idx_language` (`language`)
) DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;
