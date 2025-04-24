<?php

$files = [
// From 9.2.0 to case 11051
'/administrator/components/com_dpcalendar/forms/event_userdiscount.xml',
'/components/com_dpcalendar/tmpl/bookingform/default_coupon.php',
'/components/com_dpcalendar/tmpl/event/default_bookings_earlybird.php',
'/components/com_dpcalendar/tmpl/event/default_bookings_user.php',

// From v9.2.0 to case 11074
'/administrator/components/com_dpcalendar/forms/event_userdiscount.xml',
'/components/com_dpcalendar/tmpl/bookingform/default_coupon.php',
'/components/com_dpcalendar/tmpl/event/default_bookings_earlybird.php',
'/components/com_dpcalendar/tmpl/event/default_bookings_user.php',

// From 9.2.0 to case 945
'/components/com_dpcalendar/tmpl/calendar/default_toggle.php',
'/components/com_dpcalendar/tmpl/calendar/timeline_toggle.php',

// From 9.2.0 to case 11132
'/administrator/components/com_dpcalendar/forms/event_earlybird.xml',

// From 9.2.0 to case 11172
'/components/com_dpcalendar/tmpl/bookings/default_header.php',
'/components/com_dpcalendar/tmpl/tickets/default_header.php',

// From v9.2.1 to case 11168
'/media/com_dpcalendar/css/dpcalendar/fields/dpcalendar.css',
'/media/com_dpcalendar/css/dpcalendar/fields/dpcalendar.css.map',
'/media/com_dpcalendar/css/dpcalendar/fields/dptoken.css',
'/media/com_dpcalendar/css/dpcalendar/fields/dptoken.css.map',
'/media/com_dpcalendar/css/dpcalendar/views/adminevent/default.css',
'/media/com_dpcalendar/css/dpcalendar/views/adminevent/default.css.map',
'/media/com_dpcalendar/css/dpcalendar/views/adminform/default.css',
'/media/com_dpcalendar/css/dpcalendar/views/adminform/default.css.map',
'/media/com_dpcalendar/css/dpcalendar/views/adminlist/default.css',
'/media/com_dpcalendar/css/dpcalendar/views/adminlist/default.css.map',
'/media/com_dpcalendar/css/dpcalendar/views/booking/abort.css',
'/media/com_dpcalendar/css/dpcalendar/views/booking/abort.css.map',
'/media/com_dpcalendar/css/dpcalendar/views/booking/cancel.css',
'/media/com_dpcalendar/css/dpcalendar/views/booking/cancel.css.map',
'/media/com_dpcalendar/css/dpcalendar/views/booking/confirm.css',
'/media/com_dpcalendar/css/dpcalendar/views/booking/confirm.css.map',
'/media/com_dpcalendar/css/dpcalendar/views/booking/default.css',
'/media/com_dpcalendar/css/dpcalendar/views/booking/default.css.map',
'/media/com_dpcalendar/css/dpcalendar/views/booking/order.css',
'/media/com_dpcalendar/css/dpcalendar/views/booking/order.css.map',
'/media/com_dpcalendar/css/dpcalendar/views/booking/review.css',
'/media/com_dpcalendar/css/dpcalendar/views/booking/review.css.map',
'/media/com_dpcalendar/css/dpcalendar/views/bookingform/default.css',
'/media/com_dpcalendar/css/dpcalendar/views/bookingform/default.css.map',
'/media/com_dpcalendar/css/dpcalendar/views/bookings/default.css',
'/media/com_dpcalendar/css/dpcalendar/views/bookings/default.css.map',
'/media/com_dpcalendar/css/dpcalendar/views/calendar/default.css',
'/media/com_dpcalendar/css/dpcalendar/views/calendar/default.css.map',
'/media/com_dpcalendar/css/dpcalendar/views/calendar/timeline.css',
'/media/com_dpcalendar/css/dpcalendar/views/calendar/timeline.css.map',
'/media/com_dpcalendar/css/dpcalendar/views/cpanel/default.css',
'/media/com_dpcalendar/css/dpcalendar/views/cpanel/default.css.map',
'/media/com_dpcalendar/css/dpcalendar/views/davcalendar/default.css',
'/media/com_dpcalendar/css/dpcalendar/views/davcalendar/default.css.map',
'/media/com_dpcalendar/css/dpcalendar/views/event/default.css',
'/media/com_dpcalendar/css/dpcalendar/views/event/default.css.map',
'/media/com_dpcalendar/css/dpcalendar/views/event/mailtickets.css',
'/media/com_dpcalendar/css/dpcalendar/views/event/mailtickets.css.map',
'/media/com_dpcalendar/css/dpcalendar/views/form/default.css',
'/media/com_dpcalendar/css/dpcalendar/views/form/default.css.map',
'/media/com_dpcalendar/css/dpcalendar/views/invite/default.css',
'/media/com_dpcalendar/css/dpcalendar/views/invite/default.css.map',
'/media/com_dpcalendar/css/dpcalendar/views/list/blog.css',
'/media/com_dpcalendar/css/dpcalendar/views/list/blog.css.map',
'/media/com_dpcalendar/css/dpcalendar/views/list/default.css',
'/media/com_dpcalendar/css/dpcalendar/views/list/default.css.map',
'/media/com_dpcalendar/css/dpcalendar/views/list/timeline.css',
'/media/com_dpcalendar/css/dpcalendar/views/list/timeline.css.map',
'/media/com_dpcalendar/css/dpcalendar/views/location/default.css',
'/media/com_dpcalendar/css/dpcalendar/views/location/default.css.map',
'/media/com_dpcalendar/css/dpcalendar/views/locationform/default.css',
'/media/com_dpcalendar/css/dpcalendar/views/locationform/default.css.map',
'/media/com_dpcalendar/css/dpcalendar/views/locations/default.css',
'/media/com_dpcalendar/css/dpcalendar/views/locations/default.css.map',
'/media/com_dpcalendar/css/dpcalendar/views/map/default.css',
'/media/com_dpcalendar/css/dpcalendar/views/map/default.css.map',
'/media/com_dpcalendar/css/dpcalendar/views/profile/default.css',
'/media/com_dpcalendar/css/dpcalendar/views/profile/default.css.map',
'/media/com_dpcalendar/css/dpcalendar/views/ticket/default.css',
'/media/com_dpcalendar/css/dpcalendar/views/ticket/default.css.map',
'/media/com_dpcalendar/css/dpcalendar/views/ticketform/default.css',
'/media/com_dpcalendar/css/dpcalendar/views/ticketform/default.css.map',
'/media/com_dpcalendar/css/dpcalendar/views/tickets/default.css',
'/media/com_dpcalendar/css/dpcalendar/views/tickets/default.css.map',
'/media/com_dpcalendar/css/dpcalendar/views/tools/default.css',
'/media/com_dpcalendar/css/dpcalendar/views/tools/default.css.map',
'/media/com_dpcalendar/css/dpcalendar/views/tools/import.css',
'/media/com_dpcalendar/css/dpcalendar/views/tools/import.css.map',
'/media/com_dpcalendar/css/dpcalendar/views/tools/translate.css',
'/media/com_dpcalendar/css/dpcalendar/views/tools/translate.css.map',
'/media/com_dpcalendar/js/dayjs',
'/media/com_dpcalendar/js/domurl',
'/media/com_dpcalendar/js/dpcalendar',
'/media/com_dpcalendar/js/fullcalendar',
'/media/com_dpcalendar/js/iframe-resizer',
'/media/com_dpcalendar/js/leaflet',
'/media/com_dpcalendar/js/pikaday',
'/media/com_dpcalendar/js/popper',
'/media/com_dpcalendar/js/scheduler',
'/media/com_dpcalendar/js/tingle',
'/media/com_dpcalendar/js/tippy',
'/media/mod_dpcalendar_counter/css/default.css',
'/media/mod_dpcalendar_counter/css/default.css.map',
'/media/mod_dpcalendar_counter/js/default.js',
'/media/mod_dpcalendar_counter/js/default.js.map',
'/media/mod_dpcalendar_map/css/default.css',
'/media/mod_dpcalendar_map/css/default.css.map',
'/media/mod_dpcalendar_mini/css/default.css',
'/media/mod_dpcalendar_mini/css/default.css.map',
'/media/mod_dpcalendar_upcoming/css/blog.css',
'/media/mod_dpcalendar_upcoming/css/blog.css.map',
'/media/mod_dpcalendar_upcoming/css/default.css',
'/media/mod_dpcalendar_upcoming/css/default.css.map',
'/media/mod_dpcalendar_upcoming/css/horizontal.css',
'/media/mod_dpcalendar_upcoming/css/horizontal.css.map',
'/media/mod_dpcalendar_upcoming/css/icon.css',
'/media/mod_dpcalendar_upcoming/css/icon.css.map',
'/media/mod_dpcalendar_upcoming/css/panel.css',
'/media/mod_dpcalendar_upcoming/css/panel.css.map',
'/media/mod_dpcalendar_upcoming/css/simple.css',
'/media/mod_dpcalendar_upcoming/css/simple.css.map',
'/media/mod_dpcalendar_upcoming/css/timeline.css',
'/media/mod_dpcalendar_upcoming/css/timeline.css.map',
'/media/mod_dpcalendar_upcoming/js/default.js',
'/media/mod_dpcalendar_upcoming/js/default.js.map',
'/media/plg_content_dpcalendar/css/bookings.css',
'/media/plg_content_dpcalendar/css/bookings.css.map',
'/media/plg_content_dpcalendar/css/events.css',
'/media/plg_content_dpcalendar/css/events.css.map',
'/media/plg_dpcalendar_jitsi/css/join.css',
'/media/plg_dpcalendar_jitsi/css/join.css.map',
'/media/plg_dpcalendar_jitsi/js/join.js',
'/media/plg_dpcalendar_jitsi/js/join.js.map',
'/media/plg_dpcalendar_zoom/css/join.css',
'/media/plg_dpcalendar_zoom/css/join.css.map',
'/media/plg_dpcalendar_zoom/css/recordings.css',
'/media/plg_dpcalendar_zoom/css/recordings.css.map',
'/media/plg_dpcalendar_zoom/js/dpcalendar',
'/media/plg_dpcalendar_zoom/js/zoom',
'/media/plg_dpcalendarpay_braintree/css/form.css',
'/media/plg_dpcalendarpay_braintree/css/form.css.map',
'/media/plg_dpcalendarpay_braintree/js/form.js',
'/media/plg_dpcalendarpay_braintree/js/form.js.map',
'/media/plg_dpcalendarpay_stripe/css/form.css',
'/media/plg_dpcalendarpay_stripe/css/form.css.map',
'/media/plg_dpcalendarpay_stripe/js/form.js',
'/media/plg_dpcalendarpay_stripe/js/form.js.map',
'/media/plg_system_dpcalendarrsform/css/default.css',
'/media/plg_system_dpcalendarrsform/css/default.css.map',
'/media/plg_system_dpcalendarrsform/js/default.js',
'/media/plg_system_dpcalendarrsform/js/default.js.map',
'/media/plg_system_dpcalendarytp/css/default.css',
'/media/plg_system_dpcalendarytp/css/default.css.map',

// From v10.0.1 to case 11200
'/media/com_dpcalendar/js/vendor/popperjs/core/index.min.js',

// From v10.0.1 to case 11201
'/media/com_dpcalendar/css/leaflet/leaflet.css.map',
'/media/com_dpcalendar/css/leaflet/leaflet.min.css',
'/media/com_dpcalendar/css/leaflet/mapbox.min.css',
'/media/com_dpcalendar/css/pikaday',
'/media/com_dpcalendar/css/tingle',
'/media/com_dpcalendar/css/tippy',
'/media/com_dpcalendar/js/modules/block/loader.min.js',
'/media/com_dpcalendar/js/vendor/popperjs/core/index.min.js',
'/media/mod_dpcalendar/upcoming/js/modules/block',

// From v10.1.0 to case 11304
'/administrator/components/com_dpcalendar/vendor/doctrine/deprecations/lib',
'/administrator/components/com_dpcalendar/vendor/league',
'/plugins/dpcalendar/spreadsheet/vendor/phpoffice/phpspreadsheet/.readthedocs.yaml',
'/plugins/dpcalendar/spreadsheet/vendor/phpoffice/phpspreadsheet/src/PhpSpreadsheet/Writer/Xls/Style/ColorMap.php',
'/plugins/dpcalendarpay/qr/vendor/symfony/intl/Resources/data/scripts/ha_NE.php',

// From v10.1.3 to update dependencies
'/administrator/components/com_dpcalendar/sql/updates/mysql/8.0.0.sql',
'/administrator/components/com_dpcalendar/sql/updates/mysql/8.1.0.sql',
'/administrator/components/com_dpcalendar/sql/updates/mysql/8.11.0.sql',
'/administrator/components/com_dpcalendar/sql/updates/mysql/8.15.0.sql',
'/administrator/components/com_dpcalendar/sql/updates/mysql/8.2.0.sql',
'/administrator/components/com_dpcalendar/sql/updates/mysql/8.2.4.sql',
'/administrator/components/com_dpcalendar/sql/updates/mysql/8.3.0.sql',
'/administrator/components/com_dpcalendar/sql/updates/mysql/8.4.0.sql',
'/administrator/components/com_dpcalendar/sql/updates/mysql/8.5.0.sql',
'/administrator/components/com_dpcalendar/sql/updates/mysql/8.6.0.sql',
'/administrator/components/com_dpcalendar/sql/updates/mysql/8.9.0.sql',
'/plugins/dpcalendarpay/braintree/vendor/braintree/braintree_php/BTStandard/Sniffs/PreferIsSetSniff.php',

// From v10.3.0 to case 11540
'/media/com_dpcalendar/css/leaflet',
'/media/com_dpcalendar/js/vendor/leaflet-fullscreen',
'/media/mod_dpcalendar/upcoming/js/vendor/leaflet-fullscreen',
];

foreach ($files as $file) {
	$fullPath = JPATH_ROOT . $file;

	if (empty($file) || !file_exists($fullPath)) {
		continue;
	}

	if (is_file($fullPath)) {
		unlink($fullPath);
		continue;
	}

	try {
		$files = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator($fullPath, RecursiveDirectoryIterator::SKIP_DOTS),
			RecursiveIteratorIterator::CHILD_FIRST
		);

		foreach ($files as $fileinfo) {
			$todo = $fileinfo->isDir() ? 'rmdir' : 'unlink';
			$todo($fileinfo->getRealPath());
		}

		rmdir($fullPath);
	} catch (Exception $e) {
	}
}
