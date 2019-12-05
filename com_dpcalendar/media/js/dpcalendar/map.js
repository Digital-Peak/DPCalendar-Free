DPCalendar = window.DPCalendar || {};

(function (document, Joomla, DPCalendar) {
	'use strict';

	DPCalendar.Map = {};

	DPCalendar.Map.create = function (element) {
		if (typeof L === 'undefined') {
			return;
		}
		element.classList.add('dp-map_loading');

		var options = element.dataset;

		var map = L.map(element, {
			attributionControl: true,
			fullscreenControl: true,
			gestureHandling: true,
			gestureHandlingOptions: {
				duration: 2000,
				text: {
					touch: Joomla.JText._('COM_DPCALENDAR_LEAFLET_TEXT_TOUCH'),
					scroll: Joomla.JText._('COM_DPCALENDAR_LEAFLET_TEXT_SCROLL'),
					scrollMac: Joomla.JText._('COM_DPCALENDAR_LEAFLET_TEXT_SCROLLMAC')
				}
			}
		}).setView(
			[options.latitude ? options.latitude : 47, options.longitude ? options.longitude : 4],
			options.zoom ? options.zoom : 4
		);

		map.attributionControl.setPrefix('');
		map.attributionControl.addAttribution(Joomla.getOptions('DPCalendar.map.tiles.attribution'));

		if (Joomla.getOptions('DPCalendar.map.tiles.url') == 'google') {
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
			var tiles = L.gridLayer.googleMutant({type: type});
			tiles.addTo(map);
			tiles.addEventListener('spawned', function (e) {
				google.maps.event.addListenerOnce(e.mapObject, 'idle', function () {
					element.classList.remove('dp-map_loading');
					element.classList.add('dp-map_loaded');
				});
			});
		} else {
			var tiles = L.tileLayer(Joomla.getOptions('DPCalendar.map.tiles.url'), {id: 'mapbox.streets'});
			tiles.on('load', function () {
				element.classList.remove('dp-map_loading');
				element.classList.add('dp-map_loaded');
			});
			tiles.addTo(map);
		}

		// Marker cluster
		map.dpMarkersCluster = L.markerClusterGroup();
		map.addLayer(map.dpMarkersCluster);

		map.dpBounds = new L.latLngBounds();
		map.dpMarkers = [];
		map.dpElement = element;

		element.dpmap = map;

		var e = document.createEvent('CustomEvent');
		e.initCustomEvent('dp-map-loaded');
		element.dispatchEvent(e);

		if (Array.isArray(element.dpCachedMarkers)) {
			element.dpCachedMarkers.forEach(function (marker) {
				DPCalendar.Map.createMarker(element, marker.data, marker.dragCallback);
			});
			element.dpCachedMarkers = null;
		}

		return map;
	};

	DPCalendar.Map.createMarker = function (map, data, dragCallback) {
		var latitude = data.latitude;
		var longitude = data.longitude;
		if (latitude == null || latitude == '') {
			return;
		}

		if (map.dpmap == null) {
			if (map.dpCachedMarkers == null) {
				map.dpCachedMarkers = [];
			}
			map.dpCachedMarkers.push({data: data, dragCallback: dragCallback});

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

		map.dpmap.dpMarkersCluster.addLayer(marker);
		map.dpmap.dpMarkers.push(marker);
		map.dpmap.dpBounds.extend(marker.getLatLng());
		map.dpmap.panTo(map.dpmap.dpBounds.getCenter());

		return marker;
	};

	DPCalendar.Map.clearMarkers = function (map) {
		if (map == null || map.dpmap == null || map.dpmap.dpMarkers == null) {
			return;
		}

		for (var i = 0; i < map.dpmap.dpMarkers.length; i++) {
			map.dpmap.removeLayer(map.dpmap.dpMarkers[i]);
		}
		map.dpmap.dpMarkers = [];
		map.dpmap.dpBounds = new L.latLngBounds();

		var options = map.dpmap.dpElement.dataset;
		map.dpmap.panTo([options.latitude ? options.latitude : 47, options.longitude ? options.longitude : 4]);
	};

	DPCalendar.Map.moveMarker = function (map, marker, latitude, longitude) {
		if (!marker || map.dpmap == null) {
			return;
		}

		marker.setLatLng([latitude, longitude]);

		map.dpmap.dpBounds = new L.latLngBounds();

		map.dpmap.dpMarkers.forEach(function (m) {
			map.dpmap.dpBounds.extend(marker.getLatLng());
		});

		map.dpmap.panTo(map.dpmap.dpBounds.getCenter());
	};

	DPCalendar.Map.drawCircle = function (map, location, radius, type) {
		if (map.dpmap == null) {
			return;
		}

		if (type == 'mile') {
			radius = radius * 1.60934;
		}
		map.dpmap.dpMarkers.push(L.circle([location.latitude, location.longitude], radius * 1000).addTo(map.dpmap));
		map.dpmap.dpMarkers.push(L.circleMarker([location.latitude, location.longitude], {
			radius: 10,
			color: '#000000',
			fillColor: '#000000',
			fillOpacity: 1
		}).addTo(map.dpmap));
		map.dpmap.panTo([location.latitude, location.longitude]);
	};
})(document, Joomla, DPCalendar);
