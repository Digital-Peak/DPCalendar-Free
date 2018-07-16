DPCalendar = window.DPCalendar || {};

(function (document, Joomla, DPCalendar) {
	'use strict';

	DPCalendar.Map = {};

	DPCalendar.Map.create = function (element) {
		if (typeof L === 'undefined') {
			return;
		}

		var options = element.dataset;

		var map = L.map(element, {attributionControl: true}).setView(
			[options.latitude ? options.latitude : 47, options.longitude ? options.longitude : 4],
			options.zoom ? options.zoom : 4
		);

		map.attributionControl.setPrefix('');
		map.attributionControl.addAttribution(Joomla.getOptions('DPCalendar.map.tiles.attribution'));

		L.tileLayer(Joomla.getOptions('DPCalendar.map.tiles.url'), {
			id: 'mapbox.streets'
		}).addTo(map);
		map.dpBounds = new L.latLngBounds();
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

		if (!data.color) {
			data.color = '000000';
		}

		var markerParams = {draggable: dragCallback != null};
		markerParams.icon = L.divIcon({
			className: "dp-location-marker",
			html: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512"><path fill="#' + String(data.color).replace('#', '') + '" d="M172.268 501.67C26.97 291.031 0 269.413 0 192 0 85.961 85.961 0 192 0s192 85.961 192 192c0 77.413-26.97 99.031-172.268 309.67-9.535 13.774-29.93 13.773-39.464 0zM192 272c44.183 0 80-35.817 80-80s-35.817-80-80-80-80 35.817-80 80 35.817 80 80 80z"/></svg>',
			iconSize: [25, 30],
			iconAnchor: [10, 35]
		});

		var marker = L.marker([latitude, longitude], markerParams);
		marker.addTo(map);

		var desc = data.description ? data.description : data.title;
		if (desc) {
			var popup = marker.bindPopup(desc);
			marker.on('click', function (e) {
				popup.openPopup();
			});
		}

		if (dragCallback) {
			marker.on('dragend', function (event) {
				dragCallback(event.target.getLatLng().lat, event.target.getLatLng().lng);
			});
		}

		map.dpMarkers.push(marker);
		map.dpBounds.extend(marker.getLatLng());
		map.panTo(map.dpBounds.getCenter());

		return marker;
	};

	DPCalendar.Map.clearMarkers = function (map) {
		if (map == null || map.dpMarkers == null) {
			return;
		}

		for (var i = 0; i < map.dpMarkers.length; i++) {
			map.removeLayer(map.dpMarkers[i]);
		}
		map.dpMarkers = [];
		map.dpBounds = new L.latLngBounds();

		var options = map.dpElement.dataset;
		map.panTo([options.latitude ? options.latitude : 47, options.longitude ? options.longitude : 4]);
	};

	DPCalendar.Map.moveMarker = function (map, marker, latitude, longitude) {
		marker.setLatLng([latitude, longitude]);

		map.dpBounds = new L.latLngBounds();

		map.dpMarkers.forEach(function (m) {
			map.dpBounds.extend(marker.getLatLng());
		});

		map.panTo(map.dpBounds.getCenter());
	};

	DPCalendar.Map.drawCircle = function (map, location, radius, type) {
		if (type == 'mile') {
			radius = radius * 1.60934;
		}
		map.dpMarkers.push(L.circle([location.latitude, location.longitude], radius * 1000).addTo(map));
		map.dpMarkers.push(L.circleMarker([location.latitude, location.longitude], {
			radius: 10,
			color: '#000000',
			fillColor: '#000000',
			fillOpacity: 1
		}).addTo(map));
		map.panTo([location.latitude, location.longitude]);
	};
})(document, Joomla, DPCalendar);
