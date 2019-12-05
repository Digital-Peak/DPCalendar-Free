CREATE TABLE IF NOT EXISTS `#__dpcalendar_events` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `catid` varchar(255) NOT NULL DEFAULT '0',
  `uid` varchar(255) NOT NULL DEFAULT '',
  `original_id` int(11) DEFAULT NULL,
  `title` varchar(250) NOT NULL DEFAULT '',
  `alias` varchar(255) NOT NULL DEFAULT '',
  `rrule` varchar(255) DEFAULT NULL,
  `recurrence_id` varchar(255) DEFAULT NULL,
  `start_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `end_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `show_end_time` tinyint(3) unsigned NOT NULL DEFAULT '1',
  `all_day` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `color` varchar(250) NOT NULL DEFAULT '',
  `url` varchar(250) NOT NULL DEFAULT '',
  `images` text,
  `description` text,
  `schedule` text,
  `date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `hits` int(11) NOT NULL DEFAULT '0',
  `capacity` int(11) DEFAULT NULL,
  `capacity_used` int(11) DEFAULT 0,
  `max_tickets` int(11) DEFAULT 1,
  `booking_series` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `booking_closing_date` VARCHAR(255) DEFAULT NULL,
  `price` text,
  `earlybird` text,
  `user_discount` text,
  `booking_information` text,
  `booking_options` text,
  `ordertext` text,
  `orderurl` VARCHAR(255) DEFAULT NULL,
  `canceltext` text,
  `cancelurl` VARCHAR(255) DEFAULT NULL,
  `terms` VARCHAR(255) DEFAULT NULL,
  `rooms` text,
  `state` tinyint(1) NOT NULL DEFAULT '0',
  `checked_out` int(11) NOT NULL DEFAULT '0',
  `checked_out_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `access` int(11) NOT NULL DEFAULT '1',
  `access_content` int(11) NOT NULL DEFAULT '1',
  `params` text,
  `language` char(7) NOT NULL DEFAULT '',
  `created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `created_by` int(10) unsigned NOT NULL DEFAULT '0',
  `created_by_alias` varchar(255) NOT NULL DEFAULT '',
  `modified` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `modified_by` int(10) unsigned NOT NULL DEFAULT '0',
  `metakey` text,
  `metadesc` text,
  `metadata` text,
  `featured` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT 'Set if link is featured.',
  `xreference` varchar(255) NULL COMMENT 'A reference to enable linkages to external data sets.',
  `publish_up` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `publish_down` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `plugintype` text,
  PRIMARY KEY (`id`),
  KEY `idx_access` (`access`),
  KEY `idx_start_date` (`start_date`),
  KEY `idx_end_date` (`end_date`),
  KEY `idx_checkout` (`checked_out`),
  KEY `idx_state` (`state`),
  KEY `idx_catid` (`catid`),
  KEY `idx_createdby` (`created_by`),
  KEY `idx_featured_catid` (`featured`,`catid`),
  KEY `idx_language` (`language`),
  KEY `idx_xreference` (`xreference`)
) DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

INSERT INTO `#__content_types` (`type_title`, `type_alias`, `table`, `rules`, `field_mappings`, `router`, `content_history_options`) VALUES
('Event', 'com_dpcalendar.event', '{"special":{"dbtable":"#__dpcalendar_events","key":"id","type":"Event","prefix":"DPCalendarTable","config":"array()"},"common":{"dbtable":"#__ucm_content","key":"ucm_id","type":"Event","prefix":"DPCalendarTable","config":"array()"}}', '', '{"common":{"core_content_item_id":"id","core_title":"title","core_state":"state","core_alias":"alias","core_created_time":"created","core_modified_time":"modified","core_body":"description", "core_hits":"hits","core_publish_up":"publish_up","core_publish_down":"publish_down","core_access":"access", "core_params":"attribs","core_featured":"featured", "core_metadata":"metadata", "core_language":"language", "core_images":"images", "core_urls":"url", "core_metakey":"metakey", "core_metadesc":"metadesc", "core_catid":"catid","core_xreference":"xreference", "asset_id":"asset_id"}, "special":{}}', 'DPCalendarHelperRoute::getEventRoute', '{"formFile":"administrator\\/components\\/com_dpcalendar\\/models\\/forms\\/event.xml", "hideFields":["asset_id","checked_out", "date", "orderurl", "cancelurl","checked_out_time"],"ignoreChanges":["modified_by", "modified", "checked_out", "checked_out_time", "hits"],"convertToInt":["publish_up", "publish_down", "featured"],"displayLookup":[{"sourceColumn":"catid","targetTable":"#__categories","targetColumn":"id","displayColumn":"title"},{"sourceColumn":"created_by","targetTable":"#__users","targetColumn":"id","displayColumn":"name"},{"sourceColumn":"access","targetTable":"#__viewlevels","targetColumn":"id","displayColumn":"title"},{"sourceColumn":"modified_by","targetTable":"#__users","targetColumn":"id","displayColumn":"name"} ]}'),
('DPCalendar Category', 'com_dpcalendar.category', '{"special":{"dbtable":"#__categories","key":"id","type":"Category","prefix":"JTable","config":"array()"},"common":{"dbtable":"#__ucm_content","key":"ucm_id","type":"Corecontent","prefix":"JTable","config":"array()"}}', '', '{"common":{"core_content_item_id":"id","core_title":"title","core_state":"published","core_alias":"alias","core_created_time":"created_time","core_modified_time":"modified_time","core_body":"description","core_hits":"hits","core_publish_up":"null","core_publish_down":"null","core_access":"access", "core_params":"params","core_featured":"null", "core_metadata":"metadata", "core_language":"language", "core_images":"null", "core_urls":"null","core_version":"version", "core_ordering":"null", "core_metakey":"metakey", "core_metadesc":"metadesc", "core_catid":"parent_id","core_xreference":"null", "asset_id":"asset_id"}, "special": {"parent_id":"parent_id","lft":"lft","rgt":"rgt","level":"level","path":"path","extension":"extension","note":"note"}}', 'DPCalendarHelperRoute::getCalendarRoute', '');

CREATE TABLE IF NOT EXISTS `#__dpcalendar_locations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL DEFAULT '',
  `alias` varchar(255) NOT NULL DEFAULT '',
  `country` varchar(255) NOT NULL DEFAULT '',
  `province` varchar(255) NOT NULL DEFAULT '',
  `city` varchar(255) NOT NULL DEFAULT '',
  `zip` varchar(255) NOT NULL DEFAULT '',
  `street` varchar(255) NOT NULL DEFAULT '',
  `number` varchar(255) NOT NULL DEFAULT '',
  `rooms` text,
  `latitude` DECIMAL(12, 8) DEFAULT NULL,
  `longitude` DECIMAL(12, 8) DEFAULT NULL,
  `url` varchar(250) NOT NULL DEFAULT '',
  `description` text,
  `color` varchar(250) NOT NULL DEFAULT '',
  `date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `state` tinyint(1) NOT NULL DEFAULT '0',
  `checked_out` int(11) NOT NULL DEFAULT '0',
  `checked_out_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `ordering` int(11) NOT NULL DEFAULT '0',
  `params` text,
  `language` char(7) NOT NULL DEFAULT '',
  `created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `created_by` int(10) unsigned NOT NULL DEFAULT '0',
  `created_by_alias` varchar(255) NOT NULL DEFAULT '',
  `version` int(10) unsigned NOT NULL DEFAULT '0',
  `modified` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `modified_by` int(10) unsigned NOT NULL DEFAULT '0',
  `publish_up` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `publish_down` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `metadata` text,
  PRIMARY KEY (`id`),
  KEY `idx_checkout` (`checked_out`),
  KEY `idx_state` (`state`),
  KEY `idx_createdby` (`created_by`),
  KEY `idx_language` (`language`)
) DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE IF NOT EXISTS `#__dpcalendar_events_location` (
  `event_id` int(11) NOT NULL DEFAULT '0',
  `location_id` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`event_id`,`location_id`)
) DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `#__dpcalendar_bookings` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL DEFAULT '0',
  `uid` varchar(255) NOT NULL DEFAULT '',
  `email` varchar(255) DEFAULT NULL,
  `telephone` varchar(255) DEFAULT NULL,
  `country` varchar(255) NOT NULL DEFAULT '',
  `province` varchar(255) NOT NULL DEFAULT '',
  `city` varchar(255) NOT NULL DEFAULT '',
  `zip` varchar(255) NOT NULL DEFAULT '',
  `street` varchar(255) NOT NULL DEFAULT '',
  `number` varchar(255) NOT NULL DEFAULT '',
  `latitude` DECIMAL(12, 8) DEFAULT NULL,
  `longitude` DECIMAL(12, 8) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `book_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `options` VARCHAR(255) DEFAULT NULL,
  `state` tinyint(1) NOT NULL DEFAULT '0',
  `transaction_id` varchar(255) DEFAULT NULL,
  `price` DECIMAL(10, 2) NOT NULL DEFAULT '0.00',
  `currency` VARCHAR(10) DEFAULT NULL,
  `invoice` longtext,
  `tax` DECIMAL(10, 2) NOT NULL DEFAULT '0.00',
  `tax_rate` DECIMAL(10, 5) DEFAULT NULL,
  `processor` VARCHAR(255) DEFAULT NULL,
  `net_amount` DECIMAL(10, 2) NOT NULL DEFAULT '0.00',
  `tax_amount` DECIMAL(10, 2) NOT NULL DEFAULT '0.00',
  `gross_amount` DECIMAL(10, 2) NOT NULL DEFAULT '0.00',
  `payment_fee` DECIMAL(10, 2) NOT NULL DEFAULT '0.00',
  `tax_percent` FLOAT DEFAULT NULL,
  `txn_type` VARCHAR(255) DEFAULT NULL,
  `txn_currency` VARCHAR(10) DEFAULT NULL,
  `payer_id` VARCHAR(255) DEFAULT NULL,
  `payer_email` VARCHAR(255) DEFAULT NULL,
  `raw_data` text,
  PRIMARY KEY (`id`),
  KEY `uid` (`uid`),
  KEY `user_id` (`user_id`),
  KEY `state` (`state`)
) DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `#__dpcalendar_tickets` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `booking_id` int(11) NOT NULL DEFAULT 0,
  `event_id` int(11) NOT NULL DEFAULT 0,
  `user_id` int(11) NOT NULL DEFAULT 0,
  `uid` varchar(255) NOT NULL DEFAULT '',
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
  `remind_time` int(11) NOT NULL DEFAULT 0,
  `remind_type` tinyint(1) NOT NULL DEFAULT '1',
  `reminder_sent_date` datetime DEFAULT NULL,
  `created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `state` tinyint(1) NOT NULL DEFAULT '0',
  `public` tinyint(1) NOT NULL DEFAULT '1',
  `price` DECIMAL(10, 2) NOT NULL DEFAULT '0.00',
  `type` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `uid` (`uid`),
  KEY `booking_id` (`booking_id`),
  KEY `event_id` (`event_id`),
  KEY `user_id` (`user_id`),
  KEY `state` (`state`),
  KEY `notify` (`reminder_sent_date`, `state`)
) DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `#__dpcalendar_extcalendars` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `asset_id` int(10) unsigned NOT NULL DEFAULT '0',
  `title` varchar(255) NOT NULL DEFAULT '',
  `alias` varchar(255) NOT NULL DEFAULT '',
  `plugin` varchar(255) NOT NULL DEFAULT '',
  `description` text,
  `color` varchar(250) NOT NULL DEFAULT '',
  `color_force` tinyint(1) NOT NULL DEFAULT '0',
  `state` tinyint(1) NOT NULL DEFAULT '0',
  `ordering` int(11) NOT NULL DEFAULT '0',
  `params` text,
  `language` char(7) NOT NULL DEFAULT '',
  `created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `created_by` int(10) unsigned NOT NULL DEFAULT '0',
  `created_by_alias` varchar(255) NOT NULL DEFAULT '',
  `version` int(10) unsigned NOT NULL DEFAULT '0',
  `modified` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `modified_by` int(10) unsigned NOT NULL DEFAULT '0',
  `publish_up` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `publish_down` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `access` int(11) NOT NULL DEFAULT '1',
  `access_content` int(11) NOT NULL DEFAULT '1',
  `sync_token` text,
  `sync_date` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_plugin` (`plugin`),
  KEY `idx_state` (`state`),
  KEY `idx_createdby` (`created_by`),
  KEY `idx_language` (`language`)
) DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE IF NOT EXISTS `#__dpcalendar_taxrates` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL DEFAULT '',
  `rate` DECIMAL(10, 5) DEFAULT NULL,
  `countries` text,
  `inclusive` tinyint(1) NOT NULL DEFAULT '0',
  `state` tinyint(1) NOT NULL DEFAULT '0',
  `checked_out` int(11) NOT NULL DEFAULT '0',
  `checked_out_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `ordering` int(11) NOT NULL DEFAULT '0',
  `created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `created_by` int(10) unsigned NOT NULL DEFAULT '0',
  `version` int(10) unsigned NOT NULL DEFAULT '0',
  `modified` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `modified_by` int(10) unsigned NOT NULL DEFAULT '0',
  `publish_up` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `publish_down` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  KEY `idx_checkout` (`checked_out`),
  KEY `idx_state` (`state`),
  KEY `idx_createdby` (`created_by`)
) DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE IF NOT EXISTS `#__dpcalendar_countries` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `short_code` varchar(10) NOT NULL,
  `state` tinyint(1) NOT NULL DEFAULT '0',
  `checked_out` int(11) NOT NULL DEFAULT '0',
  `checked_out_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `ordering` int(11) NOT NULL DEFAULT '0',
  `created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `created_by` int(10) unsigned NOT NULL DEFAULT '0',
  `version` int(10) unsigned NOT NULL DEFAULT '0',
  `modified` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `modified_by` int(10) unsigned NOT NULL DEFAULT '0',
  `publish_up` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `publish_down` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  KEY `idx_short_code` (`short_code`),
  KEY `idx_checkout` (`checked_out`),
  KEY `idx_state` (`state`),
  KEY `idx_createdby` (`created_by`)
) DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

INSERT INTO `#__dpcalendar_countries` (`short_code`, `state`) VALUES
('AF', 1),
('AX', 1),
('AL', 1),
('DZ', 1),
('AS', 1),
('AD', 1),
('AO', 1),
('AI', 1),
('AQ', 1),
('AG', 1),
('AR', 1),
('AM', 1),
('AW', 1),
('AC', 1),
('AU', 1),
('AT', 1),
('AZ', 1),
('BS', 1),
('BH', 1),
('BD', 1),
('BB', 1),
('BY', 1),
('BE', 1),
('BZ', 1),
('BJ', 1),
('BM', 1),
('BT', 1),
('BO', 1),
('BA', 1),
('BW', 1),
('BR', 1),
('IO', 1),
('VG', 1),
('BN', 1),
('BG', 1),
('BF', 1),
('BI', 1),
('KH', 1),
('CM', 1),
('CA', 1),
('IC', 1),
('CV', 1),
('BQ', 1),
('KY', 1),
('CF', 1),
('EA', 1),
('TD', 1),
('CL', 1),
('CN', 1),
('CX', 1),
('CC', 1),
('CO', 1),
('KM', 1),
('CG', 1),
('CD', 1),
('CK', 1),
('CR', 1),
('CI', 1),
('HR', 1),
('CU', 1),
('CW', 1),
('CY', 1),
('CZ', 1),
('DK', 1),
('DG', 1),
('DJ', 1),
('DM', 1),
('DO', 1),
('EC', 1),
('EG', 1),
('SV', 1),
('GQ', 1),
('ER', 1),
('EE', 1),
('SZ', 1),
('ET', 1),
('FK', 1),
('FO', 1),
('FJ', 1),
('FI', 1),
('FR', 1),
('GF', 1),
('PF', 1),
('TF', 1),
('GA', 1),
('GM', 1),
('GE', 1),
('DE', 1),
('GH', 1),
('GI', 1),
('GR', 1),
('GL', 1),
('GD', 1),
('GP', 1),
('GU', 1),
('GT', 1),
('GG', 1),
('GN', 1),
('GW', 1),
('GY', 1),
('HT', 1),
('HN', 1),
('HK', 1),
('HU', 1),
('IS', 1),
('IN', 1),
('ID', 1),
('IR', 1),
('IQ', 1),
('IE', 1),
('IM', 1),
('IL', 1),
('IT', 1),
('JM', 1),
('JP', 1),
('JE', 1),
('JO', 1),
('KZ', 1),
('KE', 1),
('KI', 1),
('XK', 1),
('KW', 1),
('KG', 1),
('LA', 1),
('LV', 1),
('LB', 1),
('LS', 1),
('LR', 1),
('LY', 1),
('LI', 1),
('LT', 1),
('LU', 1),
('MO', 1),
('MG', 1),
('MW', 1),
('MY', 1),
('MV', 1),
('ML', 1),
('MT', 1),
('MH', 1),
('MQ', 1),
('MR', 1),
('MU', 1),
('YT', 1),
('MX', 1),
('FM', 1),
('MD', 1),
('MC', 1),
('MN', 1),
('ME', 1),
('MS', 1),
('MA', 1),
('MZ', 1),
('MM', 1),
('NA', 1),
('NR', 1),
('NP', 1),
('NL', 1),
('NC', 1),
('NZ', 1),
('NI', 1),
('NE', 1),
('NG', 1),
('NU', 1),
('NF', 1),
('KP', 1),
('MK', 1),
('MP', 1),
('NO', 1),
('OM', 1),
('PK', 1),
('PW', 1),
('PS', 1),
('PA', 1),
('PG', 1),
('PY', 1),
('PE', 1),
('PH', 1),
('PN', 1),
('PL', 1),
('PT', 1),
('XA', 1),
('XB', 1),
('PR', 1),
('QA', 1),
('RE', 1),
('RO', 1),
('RU', 1),
('RW', 1),
('WS', 1),
('SM', 1),
('ST', 1),
('SA', 1),
('SN', 1),
('RS', 1),
('SC', 1),
('SL', 1),
('SG', 1),
('SX', 1),
('SK', 1),
('SI', 1),
('SB', 1),
('SO', 1),
('ZA', 1),
('GS', 1),
('KR', 1),
('SS', 1),
('ES', 1),
('LK', 1),
('BL', 1),
('SH', 1),
('KN', 1),
('LC', 1),
('MF', 1),
('PM', 1),
('VC', 1),
('SD', 1),
('SR', 1),
('SJ', 1),
('SE', 1),
('CH', 1),
('SY', 1),
('TW', 1),
('TJ', 1),
('TZ', 1),
('TH', 1),
('TL', 1),
('TG', 1),
('TK', 1),
('TO', 1),
('TT', 1),
('TA', 1),
('TN', 1),
('TR', 1),
('TM', 1),
('TC', 1),
('TV', 1),
('UG', 1),
('UA', 1),
('AE', 1),
('GB', 1),
('US', 1),
('UY', 1),
('UM', 1),
('VI', 1),
('UZ', 1),
('VU', 1),
('VA', 1),
('VE', 1),
('VN', 1),
('WF', 1),
('EH', 1),
('YE', 1),
('ZM', 1),
('ZW', 1);

CREATE TABLE #__dpcalendar_caldav_calendarobjects (
    id INT(11) UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
    calendardata MEDIUMBLOB,
    uri VARBINARY(200),
    calendarid INTEGER UNSIGNED NOT NULL,
    lastmodified INT(11) UNSIGNED,
    etag VARBINARY(32),
    size INT(11) UNSIGNED NOT NULL,
    componenttype VARBINARY(8),
    firstoccurence INT(11) UNSIGNED,
    lastoccurence INT(11) UNSIGNED,
    uid VARBINARY(200),
    UNIQUE(calendarid, uri),
    INDEX calendarid_time (calendarid, firstoccurence)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE #__dpcalendar_caldav_calendars (
    id INTEGER UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
    synctoken INTEGER UNSIGNED NOT NULL DEFAULT '1',
    components VARBINARY(21)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE #__dpcalendar_caldav_calendarinstances (
    id INTEGER UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
    calendarid INTEGER UNSIGNED NOT NULL,
    principaluri VARBINARY(100),
    access TINYINT(1) NOT NULL DEFAULT '1' COMMENT '1 = owner, 2 = read, 3 = readwrite',
    displayname VARCHAR(100),
    uri VARBINARY(200),
    description TEXT,
    calendarorder INT(11) UNSIGNED NOT NULL DEFAULT '0',
    calendarcolor VARBINARY(10),
    timezone TEXT,
    transparent TINYINT(1) NOT NULL DEFAULT '0',
    share_href VARBINARY(100),
    share_displayname VARCHAR(100),
    share_invitestatus TINYINT(1) NOT NULL DEFAULT '2' COMMENT '1 = noresponse, 2 = accepted, 3 = declined, 4 = invalid',
    UNIQUE(principaluri, uri),
    UNIQUE(calendarid, principaluri),
    UNIQUE(calendarid, share_href)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE #__dpcalendar_caldav_calendarchanges (
    id INT(11) UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
    uri VARBINARY(200) NOT NULL,
    synctoken INT(11) UNSIGNED NOT NULL,
    calendarid INT(11) UNSIGNED NOT NULL,
    operation TINYINT(1) NOT NULL,
    INDEX calendarid_synctoken (calendarid, synctoken)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE #__dpcalendar_caldav_calendarsubscriptions (
    id INT(11) UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
    uri VARBINARY(200) NOT NULL,
    principaluri VARBINARY(100) NOT NULL,
    source TEXT,
    displayname VARCHAR(100),
    refreshrate VARCHAR(10),
    calendarorder INT(11) UNSIGNED NOT NULL DEFAULT '0',
    calendarcolor VARBINARY(10),
    striptodos TINYINT(1) NULL,
    stripalarms TINYINT(1) NULL,
    stripattachments TINYINT(1) NULL,
    lastmodified INT(11) UNSIGNED,
    UNIQUE(principaluri, uri)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE #__dpcalendar_caldav_schedulingobjects (
    id INT(11) UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
    principaluri VARBINARY(255),
    calendardata MEDIUMBLOB,
    uri VARBINARY(200),
    lastmodified INT(11) UNSIGNED,
    etag VARBINARY(32),
    size INT(11) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE #__dpcalendar_caldav_principals (
    id INTEGER UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
    uri VARBINARY(200) NOT NULL,
    email VARBINARY(80),
    displayname VARCHAR(80),
    `external_id` int(11) unsigned NOT NULL,
    UNIQUE(uri)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE #__dpcalendar_caldav_groupmembers (
    id INTEGER UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
    principal_id INTEGER UNSIGNED NOT NULL,
    member_id INTEGER UNSIGNED NOT NULL,
    UNIQUE(principal_id, member_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE #__dpcalendar_caldav_propertystorage (
    id INT UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
    path VARBINARY(1024) NOT NULL,
    name VARBINARY(100) NOT NULL,
    valuetype INT UNSIGNED,
    value MEDIUMBLOB
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE UNIQUE INDEX path_property ON #__dpcalendar_caldav_propertystorage (path(600), name(100));

insert into #__dpcalendar_caldav_principals (uri, email, displayname, external_id) select concat("principals/", username) as uri, email, name as displayname, id from #__users u ON DUPLICATE KEY UPDATE email=u.email, displayname=u.name;
insert into #__dpcalendar_caldav_principals (uri, email, displayname, external_id) select concat("principals/", username, "/calendar-proxy-read") as uri, email, name as displayname, id from #__users u ON DUPLICATE KEY UPDATE email=u.email, displayname=u.name;
insert into #__dpcalendar_caldav_principals (uri, email, displayname, external_id) select concat("principals/", username, "/calendar-proxy-write") as uri, email, name as displayname, id from #__users u ON DUPLICATE KEY UPDATE email=u.email, displayname=u.name;
