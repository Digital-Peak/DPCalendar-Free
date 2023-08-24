/**
 * @package   DPCalendar
 * @copyright Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */
(function () {
	'use strict';
	function watchElements(elements) {
		elements.forEach((mapElement) => {
			if ('IntersectionObserver' in window === false) {
				loadDPAssets(['/com_dpcalendar/js/dpcalendar/map.js'], () => DPCalendar.Map.create(mapElement));
				return;
			}
			const observer = new IntersectionObserver(
				(entries, observer) => {
					entries.forEach((entry) => {
						if (!entry.isIntersecting) {
							return;
						}
						observer.unobserve(mapElement);
						loadDPAssets(['/com_dpcalendar/js/dpcalendar/map.js'], () => DPCalendar.Map.create(mapElement));
					});
				}
			);
			observer.observe(mapElement);
		});
	}
	document.addEventListener('DOMContentLoaded', () => {
		const assets = ['/com_dpcalendar/js/dpcalendar/dpcalendar.js'];
		if (document.querySelector('.dp-search-map .dp-form')) {
			assets.push('/com_dpcalendar/js/dpcalendar/layouts/block/datepicker.js');
			assets.push('/com_dpcalendar/js/dpcalendar/layouts/block/timepicker.js');
		}
		loadDPAssets(assets, () => {
			const update = (root) => {
				const mapObject = root.querySelector('.dp-map');
				if (mapObject == null || !mapObject.dpmap) {
					return;
				}
				DPCalendar.request(
					'view=map&layout=events&format=raw&' + DPCalendar.formToQueryString(root.querySelector('.dp-form:not(.dp-timezone)')),
					(json) => {
						DPCalendar.Map.clearMarkers(mapObject);
						json.data.events.forEach((event) => {
							event.location.forEach((location) => {
								const locationData = JSON.parse(JSON.stringify(location));
								locationData.title = event.title;
								locationData.color = event.color;
								locationData.description = event.description;
								DPCalendar.Map.createMarker(mapObject, locationData);
							});
						});
						if (json.data.location && root.querySelector('.dp-input[name=radius]').value != -1) {
							DPCalendar.Map.drawCircle(
								mapObject,
								json.data.location,
								root.querySelector('.dp-input[name=radius]').value,
								root.querySelector('.dp-input[name="length-type"]').value
							);
						}
					},
					null,
					true,
					'GET'
				);
			};
			[].slice.call(document.querySelectorAll('.dp-search-map')).forEach((map) => {
				[].slice.call(map.querySelectorAll('.dp-input, .dp-select:not(.dp-timezone__select)')).forEach((input) => {
					input.addEventListener('change', (event) => {
						event.preventDefault();
						update(map);
						return false;
					});
				});
				map.addEventListener('click', (event) => {
					if (!event.target || !event.target.matches('.dp-event-tooltip__link')) {
						return true;
					}
					if (window.innerWidth < 600) {
						return true;
					}
					event.preventDefault();
					const root = map.closest('.dp-search-map');
					if (root.dataset.popup == 1) {
						DPCalendar.modal(event.target.getAttribute('href'), root.dataset.popupwidth, root.dataset.popupheight);
					} else if (root.dataset.popup == 0) {
						window.location = DPCalendar.encode(event.target.getAttribute('href'));
					}
					return false;
				});
				map.querySelector('.dp-map').addEventListener('dp-map-loaded', () => update(map));
				watchElements([].slice.call(map.querySelectorAll('.dp-map')));
				const geoComplete = map.querySelector('.dp-input_location');
				if (geoComplete && geoComplete.dataset.dpAutocomplete == 1) {
					loadDPAssets(['/com_dpcalendar/js/dpcalendar/layouts/block/autocomplete.js'], () => {
						DPCalendar.autocomplete.create(geoComplete);
						geoComplete.addEventListener('dp-autocomplete-select', () => update(map));
						geoComplete.addEventListener('dp-autocomplete-change', (e) => {
							let task = 'location.searchloc';
							if (window.location.href.indexOf('administrator') == -1) {
								task = 'locationform.searchloc';
							}
							DPCalendar.request(
								'task=' + task + '&loc=' + encodeURIComponent(e.target.value.trim()),
								(json) => DPCalendar.autocomplete.setItems(geoComplete, json.data)
							);
						});
					});
				}
				let button = map.querySelector('.dp-button-search');
				if (button) {
					button.addEventListener('click', (e) => {
						e.preventDefault();
						update(map);
						return false;
					});
				}
				button = map.querySelector('.dp-button-clear');
				if (button) {
					button.addEventListener('click', (e) => {
						e.preventDefault();
						[].slice.call(map.querySelectorAll('.dp-input:not([name="Itemid"])')).forEach((input) => {
							input.value = '';
						});
						const radius = map.querySelector('[name=radius]');
						radius.value = radius.getAttribute('data-default') ? radius.getAttribute('data-default') : 20;
						const length = map.querySelector('[name=length-type]');
						length.value = length.getAttribute('data-default') ? length.getAttribute('data-default') : 'm';
						update(map);
						return false;
					});
				}
				button = map.querySelector('.dp-button-current-location');
				if (button) {
					button.addEventListener('click', (e) => {
						e.preventDefault();
						DPCalendar.currentLocation((address) => {
							const form = e.target.closest('.dp-form');
							form.querySelector('[name=location]').value = address;
							update(map);
						});
						return false;
					});
					if (!('geolocation' in navigator)) {
						button.style.display = 'none';
					}
				}
			});
		});
	});
})();
