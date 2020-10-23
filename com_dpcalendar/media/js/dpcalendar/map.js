(function () {
	'use strict';

	/**
	 * @package   DPCalendar
	 * @author    Digital Peak http://www.digital-peak.com
	 * @copyright Copyright (C) 2007 - 2020 Digital Peak. All rights reserved.
	 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
	 */

	function createMarker(map, data, dragCallback)
	{
		const latitude = data.latitude;
		const longitude = data.longitude;
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

		const markerParams = {draggable: dragCallback != null};
		markerParams.icon = L.divIcon({
			className: "dp-location-marker",
			html: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512"><path fill="#' + String(data.color).replace('#', '') + '" d="M172.268 501.67C26.97 291.031 0 269.413 0 192 0 85.961 85.961 0 192 0s192 85.961 192 192c0 77.413-26.97 99.031-172.268 309.67-9.535 13.774-29.93 13.773-39.464 0zM192 272c44.183 0 80-35.817 80-80s-35.817-80-80-80-80 35.817-80 80 35.817 80 80 80z"/></svg>',
			iconSize: [25, 30],
			iconAnchor: [10, 35]
		});

		const marker = L.marker([latitude, longitude], markerParams);

		const desc = data.description ? data.description : data.title;
		if (desc) {
			const popup = marker.bindPopup(desc);
			marker.on('click', (e) => {
				popup.openPopup();
			});
		}

		if (dragCallback) {
			marker.on('dragend', (event) => {
				dragCallback(event.target.getLatLng().lat, event.target.getLatLng().lng);
			});
		}

		map.dpmap.dpMarkersCluster.addLayer(marker);
		map.dpmap.dpMarkers.push(marker);
		map.dpmap.dpBounds.extend(marker.getLatLng());

		// Zoom out when needed to fit all markers
		const boundsZoom = map.dpmap.getBoundsZoom(map.dpmap.dpBounds);
		if (boundsZoom < map.dpmap.getZoom()) {
			map.dpmap.setZoom(boundsZoom);
		}
		map.dpmap.panTo(map.dpmap.dpBounds.getCenter());

		return marker;
	}

	function clearMarkers(map)
	{
		if (map == null || map.dpmap == null || map.dpmap.dpMarkers == null) {
			return;
		}

		map.dpmap.dpMarkers.forEach((marker) => {
			map.dpmap.dpMarkersCluster.removeLayer(marker);
		});

		map.dpmap.dpMarkers = [];
		map.dpmap.dpCachedMarkers = [];
		map.dpmap.dpBounds = new L.latLngBounds();

		const options = map.dpmap.dpElement.dataset;
		map.dpmap.panTo([options.latitude ? options.latitude : 47, options.longitude ? options.longitude : 4]);
	}

	function moveMarker(map, marker, latitude, longitude)
	{
		if (!marker || map.dpmap == null) {
			return;
		}

		marker.setLatLng([latitude, longitude]);

		map.dpmap.dpBounds = new L.latLngBounds();

		map.dpmap.dpMarkers.forEach((m) => {
			map.dpmap.dpBounds.extend(marker.getLatLng());
		});

		map.dpmap.panTo(map.dpmap.dpBounds.getCenter());
	}

	/**
	 * @package   DPCalendar
	 * @author    Digital Peak http://www.digital-peak.com
	 * @copyright Copyright (C) 2007 - 2020 Digital Peak. All rights reserved.
	 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
	 */

	function createMap(element)
	{
		if (typeof L === 'undefined') {
			return;
		}

		element.classList.add('dp-map_loading');

		const options = element.dataset;

		const map = L.map(element, {
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
			let type = google.maps.MapTypeId.ROADMAP;
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
			const tiles = L.gridLayer.googleMutant({type: type});
			tiles.addTo(map);
			tiles.addEventListener('spawned', (e) => {
				google.maps.event.addListenerOnce(e.mapObject, 'idle', () => {
					element.classList.remove('dp-map_loading');
					element.classList.add('dp-map_loaded');
				});
			});
		} else if (Joomla.getOptions('DPCalendar.map.tiles.url') == 'mapbox') {
			const gl = L.mapboxGL({
				accessToken: Joomla.getOptions('DPCalendar.map.mapbox.token'),
				style: 'mapbox://styles/mapbox/streets-v11'
			});
			gl.on('load', () => {
				element.classList.remove('dp-map_loading');
				element.classList.add('dp-map_loaded');
			});
			map._layersMaxZoom = 19;
			gl.addTo(map);
		} else {
			const tiles = L.tileLayer(Joomla.getOptions('DPCalendar.map.tiles.url'));
			tiles.on('load', () => {
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

		element.dispatchEvent(new CustomEvent('dp-map-loaded'));

		if (Array.isArray(element.dpCachedMarkers)) {
			element.dpCachedMarkers.forEach((m) => {
				createMarker(element, m.data, m.dragCallback);
			});
			element.dpCachedMarkers = null;
		}

		return map;
	}

	// Set up the maps
	function create(mapElement)
	{
		const options = mapElement.dataset;

		if (options.width) {
			mapElement.style.width = options.width;
		}

		if (options.height) {
			mapElement.style.height = options.height;
		}

		if (!localStorage.getItem('DPCalendar.map.load') && mapElement.dataset.askConsent == 1) {
			ask(mapElement);
			return;
		}

		let assets = ['/com_dpcalendar/js/leaflet/leaflet.js', '/com_dpcalendar/css/leaflet/leaflet.css'];
		if (Joomla.getOptions('DPCalendar.map.provider') == 'google') {
			assets.push('https://maps.googleapis.com/maps/api/js?libraries=places&language=' + Joomla.getOptions('DPCalendar.map.google.lang') + '&key=' + Joomla.getOptions('DPCalendar.map.google.key'));
		}

		if (Joomla.getOptions('DPCalendar.map.provider') == 'mapbox') {
			assets.push('https://api.tiles.mapbox.com/mapbox-gl-js/v1.12.0/mapbox-gl.css');
			assets.push('https://api.tiles.mapbox.com/mapbox-gl-js/v1.12.0/mapbox-gl.js');
		}

		loadDPAssets(assets, () => {
			let assets = [];
			if (Joomla.getOptions('DPCalendar.map.provider') == 'google') {
				assets.push('/com_dpcalendar/js/leaflet/leaflet-google.js');
			}
			if (Joomla.getOptions('DPCalendar.map.provider') == 'mapbox') {
				assets.push('/com_dpcalendar/js/leaflet/leaflet-mapbox.js');
			}
			loadDPAssets(assets, () => {
				createMap(mapElement);

				let locationsContainer = mapElement.closest('.dp-location');
				if (locationsContainer == null) {
					locationsContainer = mapElement.closest('.dp-locations');
				}
				if (locationsContainer == null) {
					return;
				}

				[].slice.call(locationsContainer.querySelectorAll('.dp-location__details')).forEach((location) => {
					const data = location.dataset;

					const desc = location.parentElement.querySelector('.dp-location__description');
					if (!data.description && desc) {
						data.description = desc.innerHTML;
					}
					createMarker(mapElement, data);
				});
			});
		});
	}

	function ask(element)
	{
		element.classList.add('dp-map_consent');
		element.textContent = Joomla.JText._('COM_DPCALENDAR_FIELD_CONFIG_INTEGRATION_MAP_CONSENT_INFO_TEXT');
		element.addEventListener('click', () => {
			localStorage.setItem('DPCalendar.map.load', true);
			element.classList.remove('dp-map_consent');
			element.textContent = '';
			createMap(element);
		});
	}

	/**
	 * @package   DPCalendar
	 * @author    Digital Peak http://www.digital-peak.com
	 * @copyright Copyright (C) 2007 - 2020 Digital Peak. All rights reserved.
	 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
	 */

	function drawCircle(map, location, radius, type)
	{
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
	}

	/**
	 * @package   DPCalendar
	 * @author    Digital Peak http://www.digital-peak.com
	 * @copyright Copyright (C) 2007 - 2020 Digital Peak. All rights reserved.
	 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
	 */

	const DPCalendar = window.DPCalendar || {};
	window.DPCalendar = DPCalendar;

	DPCalendar.Map = {};
	DPCalendar.Map.create = create;
	DPCalendar.Map.createMarker = createMarker;
	DPCalendar.Map.clearMarkers = clearMarkers;
	DPCalendar.Map.moveMarker = moveMarker;
	DPCalendar.Map.drawCircle = drawCircle;

}());
//# sourceMappingURL=map.js.map
