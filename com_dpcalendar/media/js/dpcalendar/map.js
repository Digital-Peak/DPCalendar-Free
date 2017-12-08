DPCalendar = window.DPCalendar || {};

(function (DPCalendar) {
	"use strict";

	document.addEventListener("DOMContentLoaded", function () {
		var maps = document.querySelectorAll('.dpcalendar-map');
		for (var i = 0; i < maps.length; i++) {
			DPCalendar.Map.create(maps[i]);
		}
	});

	DPCalendar.Map = {};

	DPCalendar.Map.create = function (element) {
		if (typeof google === 'undefined') {
			return;
		}
		var options = element.dataset;

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
		var map = new google.maps.Map(element, {
			zoom: parseInt(options.zoom ? options.zoom : 4),
			mapTypeId: type,
			center: new google.maps.LatLng(options.latitude ? options.latitude : 47, options.longitude ? options.longitude : 4),
			draggable: document.body.clientWidth > 480 ? true : false,
			scrollwheel: document.body.clientWidth > 480 ? true : false
		});
		map.dpBounds = new google.maps.LatLngBounds();
		map.dpMarkers = [];
		map.dpElement = element;

		element.dpmap = map;

		var locationsContainer = element.closest('.dpcalendar-locations-container');

		if (locationsContainer == null) {
			return map;
		}

		var locations = locationsContainer.querySelectorAll('.location-details');
		for (var i = 0; i < locations.length; i++) {
			var el = locations[i];
			var data = el.dataset;

			var desc = el.parentElement.querySelector('.location-description');
			if (!data.description && desc) {
				data.description = desc.innerHTML;
			}
			DPCalendar.Map.createMarker(map, data);
		}


		return map;
	};
	DPCalendar.Map.createMarker = function (map, data) {
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
			position: l,
			map: map,
			title: data.title,
		};

		var color = data.color;
		if (color) {
			markerOptions.icon = {
				url: chartUrl + String(color).replace('#', ''),
				size: new google.maps.Size(21, 34),
				origin: new google.maps.Point(0, 0),
				anchor: new google.maps.Point(10, 34)
			}
		}

		var marker = new google.maps.Marker(markerOptions);

		var desc = data.description ? data.description : data.title;
		if (desc) {
			var infowindow = new google.maps.InfoWindow({content: desc});
			google.maps.event.addListener(marker, 'click', function () {
				infowindow.open(map, marker);
			});
		}

		map.dpMarkers.push(marker);
		map.dpBounds.extend(l);
		map.setCenter(map.dpBounds.getCenter());

		return marker;
	};

	DPCalendar.Map.clearMarkers = function (map) {
		if (map == null || map.dpMarkers == null) {
			return;
		}

		for (var i = 0; i < map.dpMarkers.length; i++) {
			map.dpMarkers[i].setMap(null);
		}
		map.dpMarkers = [];
		map.dpBounds = new google.maps.LatLngBounds();

		var options = map.dpElement.dataset;
		map.setCenter(new google.maps.LatLng(options.latitude ? options.latitude : 47, options.longitude ? options.longitude : 4));
	};

	DPCalendar.Map.currentLocation = function (callback) {
		if (!navigator.geolocation) {
			return false;
		}
		navigator.geolocation.getCurrentPosition(function (pos) {
			var task = 'location.loc';
			if (window.location.href.indexOf('administrator') == -1) {
				task = 'locationform.loc';
			}
			Joomla.request({
				url: "index.php?option=com_dpcalendar&task=" + task + "&loc=" + encodeURIComponent(pos.coords.latitude + ',' + pos.coords.longitude),
				method: "GET",
				onSuccess: function (response) {
					var json = JSON.parse(response);

					if (json.messages != null && document.getElementById('system-message-container')) {
						Joomla.renderMessages(json.messages);
					}
					callback(json.data.formated);
				}
			});
		}, function (error) {
			Joomla.renderMessages({error: [error.message]});
		});

		return true;
	}
})
(DPCalendar);
