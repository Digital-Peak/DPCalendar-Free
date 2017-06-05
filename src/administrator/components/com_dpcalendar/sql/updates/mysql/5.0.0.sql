ALTER TABLE `#__dpcalendar_events` ADD COLUMN `images` TEXT NOT NULL DEFAULT '' AFTER `url`;
ALTER TABLE `#__dpcalendar_events` CHANGE `sid` `uid` VARCHAR(255) NOT NULL DEFAULT '';

INSERT INTO `#__content_types` (`type_title`, `type_alias`, `table`, `rules`, `field_mappings`, `router`, `content_history_options`) VALUES
('Event',
	'com_dpcalendar.event',
	'{"special":{"dbtable":"#__dpcalendar_events","key":"id","type":"Event","prefix":"DPCalendarTable","config":"array()"},
	"common":{"dbtable":"#__ucm_content","key":"ucm_id","type":"Event","prefix":"DPCalendarTable","config":"array()"}}',
	'',
	'{"common":{"core_content_item_id":"id","core_title":"title","core_state":"state","core_alias":"alias",
	"core_created_time":"created","core_modified_time":"modified","core_body":"description", "core_hits":"hits",
	"core_publish_up":"publish_up","core_publish_down":"publish_down","core_access":"access", "core_params":"attribs",
	"core_featured":"featured", "core_metadata":"metadata", "core_language":"language", "core_images":"images", "core_urls":"url",
	"core_metakey":"metakey", "core_metadesc":"metadesc", "core_catid":"catid",
	"core_xreference":"xreference", "asset_id":"asset_id"}, "special":{}}',
	'DPCalendarHelperRoute::getEventRoute',
	'{"formFile":"administrator\\/components\\/com_dpcalendar\\/models\\/forms\\/event.xml", "hideFields":["asset_id","checked_out",
	"checked_out_time"],"ignoreChanges":["modified_by", "modified", "checked_out", "checked_out_time", "hits"],
	"convertToInt":["publish_up", "publish_down", "featured"],"displayLookup":[{"sourceColumn":"catid",
	"targetTable":"#__categories","targetColumn":"id","displayColumn":"title"},
	{"sourceColumn":"created_by","targetTable":"#__users","targetColumn":"id","displayColumn":"name"},
	{"sourceColumn":"access","targetTable":"#__viewlevels","targetColumn":"id","displayColumn":"title"},
	{"sourceColumn":"modified_by","targetTable":"#__users","targetColumn":"id","displayColumn":"name"} ]}');
