DPCalendar = window.DPCalendar || {};

(function (document, Joomla, DPCalendar) {
	'use strict';

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

		return map;
	};

	DPCalendar.Map.createMarker = function (map, data, dragCallback) {
		var latitude = data.latitude;
		var longitude = data.longitude;
		if (latitude == null || latitude == '') {
			return;
		}

		var l = new google.maps.LatLng(latitude, longitude);
		var markerOptions = {position: l, map: map, title: data.title, draggable: dragCallback != null};

		if (data.color) {
			markerOptions.icon = {
				path: 'M172.268 501.67C26.97 291.031 0 269.413 0 192 0 85.961 85.961 0 192 0s192 85.961 192 192c0 77.413-26.97 99.031-172.268 309.67-9.535 13.774-29.93 13.773-39.464 0zM192 272c44.183 0 80-35.817 80-80s-35.817-80-80-80-80 35.817-80 80 35.817 80 80 80z',
				fillColor: '#' + String(data.color).replace('#', ''),
				fillOpacity: 1,
				scale: 0.07,
				anchor: new google.maps.Point(150, 512)
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

		if (dragCallback) {
			google.maps.event.addListener(marker, 'dragend', function (event) {
				dragCallback(event.latLng.lat(), event.latLng.lng());
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

	DPCalendar.Map.moveMarker = function (map, marker, latitude, longitude) {
		marker.setPosition(new google.maps.LatLng(latitude, longitude));

		map.dpBounds = new google.maps.LatLngBounds();

		map.dpMarkers.forEach(function (m) {
			map.dpBounds.extend(m.position);
		});

		map.setCenter(map.dpBounds.getCenter());
	};

	DPCalendar.Map.drawCircle = function (map, location, radius, type) {
		if (type == 'mile') {
			radius = radius * 1.60934;
		}

		map.dpMarkers.push(new google.maps.Circle({
			map: map,
			center: {lat: parseFloat(location.latitude), lng: parseFloat(location.longitude)},
			radius: radius * 1000
		}));

		var marker = new google.maps.Marker({
			map: map,
			position: {lat: parseFloat(location.latitude), lng: parseFloat(location.longitude)},
			icon: {
				path: google.maps.SymbolPath.CIRCLE,
				fillColor: '#000',
				fillOpacity: 0.6,
				strokeColor: '#00A',
				strokeOpacity: 0.9,
				strokeWeight: 1,
				scale: 6
			}
		});
		map.dpBounds.extend(marker.position);
		map.dpMarkers.push(marker);
		map.setCenter(map.dpBounds.getCenter());
	};
})(document, Joomla, DPCalendar);
