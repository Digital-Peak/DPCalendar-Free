jQuery(document).ready(function() {
	jQuery('div.dpcalendar-map').each(function(i) {
		createDPCalendarMap(jQuery(this));
	});
});

/**
 * Creates a map and returns it.
 *
 * @return map
 */
function createDPCalendarMap(element) {
	if (typeof google === 'undefined') {
		return;
	}
	var options = element.data();

	var type = google.maps.MapTypeId.ROADMAP;
	switch (options.type) {
	case 2:
		type = google.maps.MapTypeId.SATELLITE;
		break;
	case 3:
		type = google.maps.MapTypeId.HYBRID;
		break;
	case 4:
		type = google.maps.MapTypeId.TERRAIN;
		break;
	}
	var map = new google.maps.Map(document.getElementById(element.attr('id')), {
		zoom : parseInt(options.zoom ? options.zoom : 4),
		mapTypeId : type,
		center : new google.maps.LatLng(options.latitude ? options.latitude : 47, options.longitude ? options.longitude : 4),
		draggable : jQuery(document).width() > 480 ? true : false,
		scrollwheel : jQuery(document).width() > 480 ? true : false
	});
	map.dpBounds = new google.maps.LatLngBounds();
	map.dpMarkers = [];
	map.dpElement = element;

	element.closest('.dpcalendar-locations-container').find('.location-details').each(function(i) {
		var el = jQuery(this);
		var data = el.data();
		if (!data.description && el.parent().find('.location-description').length > 0) {
			data.description = el.parent().find('.location-description').html();
		}
		createDPCalendarMarker(map, data);
	});

	element.data('dpmap', map);

	return map;
}

function createDPCalendarMarker(map, data) {
	var latitude = data.latitude;
	var longitude = data.longitude;
	if (latitude == null || latitude == "") {
		return;
	}

	// Chart url for markers
	var chartUrl = 'http://chart.apis.google.com/chart?chst=d_map_pin_letter&chld=%E2%80%A2|';
	if (document.location.protocol == 'https:') {
		chartUrl = 'https://chart.googleapis.com/chart?chst=d_map_pin_letter&chld=%E2%80%A2|';
	}

	var l = new google.maps.LatLng(latitude, longitude);
	var markerOptions = {
		position : l,
		map : map,
		title : data.title,
	};

	var color = data.color;
	if (color) {
		markerOptions.icon = {
			url : chartUrl + String(color).replace('#', ''),
			size : new google.maps.Size(21, 34),
			origin : new google.maps.Point(0, 0),
			anchor : new google.maps.Point(10, 34)
		}
	}

	var marker = new google.maps.Marker(markerOptions);

	var desc = data.description ? data.description : data.title;
	if (desc) {
		var infowindow = new google.maps.InfoWindow({
			content : desc
		});
		google.maps.event.addListener(marker, 'click', function() {
			infowindow.open(map, marker);
		});
	}

	map.dpMarkers.push(marker);
	map.dpBounds.extend(l);
	map.setCenter(map.dpBounds.getCenter());

	return marker;
}

function clearDPCalendarMarkers(map) {
	if (map == null) {
		return;
	}
	jQuery.each(map.dpMarkers, function(i, marker) {
		marker.setMap(null);
	});
	map.dpMarkers = [];
	map.dpBounds = new google.maps.LatLngBounds();

	var options = map.dpElement.data();
	map.setCenter(new google.maps.LatLng(options.latitude ? options.latitude : 47, options.longitude ? options.longitude : 4));
}
