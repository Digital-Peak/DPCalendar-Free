CREATE TABLE IF NOT EXISTS `#__dpcalendar_caldav_calendarobjects` (
	`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
	`calendardata` mediumblob,
	`uri` varchar(200) COLLATE utf8_unicode_ci DEFAULT NULL,
	`calendarid` int(10) unsigned NOT NULL,
	`lastmodified` int(11) unsigned DEFAULT NULL,
	`etag` varchar(32) COLLATE utf8_unicode_ci DEFAULT NULL,
	`size` int(11) unsigned NOT NULL,
	`componenttype` varchar(8) COLLATE utf8_unicode_ci DEFAULT NULL,
	`firstoccurence` int(11) unsigned DEFAULT NULL,
	`lastoccurence` int(11) unsigned DEFAULT NULL,
	PRIMARY KEY (`id`),
	UNIQUE KEY `calendarid` (`calendarid`,`uri`)
) DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE IF NOT EXISTS `#__dpcalendar_caldav_calendars` (
	`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
	`principaluri` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
	`displayname` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
	`uri` varchar(200) COLLATE utf8_unicode_ci DEFAULT NULL,
	`ctag` int(10) unsigned NOT NULL DEFAULT '0',
	`description` text COLLATE utf8_unicode_ci,
	`calendarorder` int(10) unsigned NOT NULL DEFAULT '0',
	`calendarcolor` varchar(10) COLLATE utf8_unicode_ci DEFAULT NULL,
	`timezone` text COLLATE utf8_unicode_ci,
	`components` varchar(20) COLLATE utf8_unicode_ci DEFAULT NULL,
	`transparent` tinyint(1) NOT NULL DEFAULT '0',
	PRIMARY KEY (`id`),
	UNIQUE KEY `principaluri` (`principaluri`,`uri`)
) DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE IF NOT EXISTS `#__dpcalendar_caldav_principals` (
	`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
	`uri` varchar(200) COLLATE utf8_unicode_ci NOT NULL,
	`email` varchar(80) COLLATE utf8_unicode_ci DEFAULT NULL,
	`displayname` varchar(80) COLLATE utf8_unicode_ci DEFAULT NULL,
	`vcardurl` varchar(80) COLLATE utf8_unicode_ci DEFAULT NULL,
	`external_id` int(11) unsigned NOT NULL,
	PRIMARY KEY (`id`),
	UNIQUE KEY `uri` (`uri`),
	KEY `external_id` (`external_id`)
) DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE IF NOT EXISTS `#__dpcalendar_caldav_groupmembers` (
	`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
	`principal_id` int(10) unsigned NOT NULL,
	`member_id` int(10) unsigned NOT NULL,
	PRIMARY KEY (`id`),
	UNIQUE(principal_id, member_id)
);

INSERT INTO `#__dpcalendar_caldav_principals`
	(uri, email, displayname, external_id) select concat("principals/", username) as uri, email, name as displayname, id
	from `#__users` u ON DUPLICATE KEY UPDATE email=u.email, displayname=u.name;

INSERT INTO `#__dpcalendar_caldav_principals`
	(uri, email, displayname, external_id) select concat("principals/", username, "/calendar-proxy-read") as uri, email, name as displayname, id
	from `#__users` u ON DUPLICATE KEY UPDATE email=u.email, displayname=u.name;

INSERT INTO `#__dpcalendar_caldav_principals`
	(uri, email, displayname, external_id) select concat("principals/", username, "/calendar-proxy-write") as uri, email, name as displayname, id
	from `#__users` u ON DUPLICATE KEY UPDATE email=u.email, displayname=u.name;
