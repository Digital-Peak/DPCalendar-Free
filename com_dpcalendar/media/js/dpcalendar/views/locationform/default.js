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
	let internalChange = false;
	document.addEventListener('DOMContentLoaded', () => {
		loadDPAssets([
			'/com_dpcalendar/js/dpcalendar/dpcalendar.js',
			'/com_dpcalendar/js/dpcalendar/layouts/block/autocomplete.js',
			'/com_dpcalendar/js/dpcalendar/layouts/block/select.js'
		], () => {
			const geoComplete = document.getElementById('jform_geocomplete');
			const map = document.querySelector('.dp-map');
			const getMarker = () => {
				if (!map.dpmap.dpMarkers.length) {
					DPCalendar.Map.createMarker(
						map,
						{
							latitude: document.getElementById('jform_latitude').value,
							longitude: document.getElementById('jform_longitude').value,
							color: document.getElementById('jform_color').value
						},
						(latitude, longitude) => {
							if (latitude) {
								document.getElementById('jform_latitude').value = latitude;
							}
							if (longitude) {
								document.getElementById('jform_longitude').value = longitude;
							}
						}
					);
				}
				if (!map.dpmap.dpMarkers.length) {
					return;
				}
				return map.dpmap.dpMarkers[0];
			};
			const mapLoader = () => {
				[].slice.call(document.querySelectorAll('#jform_street,#jform_number,#jform_zip,#jform_city,#jform_country,#jform_province')).forEach((input) => {
					input.addEventListener('change', () => {
						if (internalChange) {
							return;
						}
						geoComplete.value = '';
						let task = 'location.loc';
						if (window.location.href.indexOf('administrator') == -1) {
							task = 'locationform.loc';
						}
						DPCalendar.request(
							'task=' + task + '&loc=' + encodeURIComponent(getAddresString()),
							(json) => {
								if (json.data.latitude) {
									document.getElementById('jform_latitude').value = json.data.latitude;
									document.getElementById('jform_longitude').value = json.data.longitude;
									DPCalendar.Map.moveMarker(map, getMarker(), json.data.latitude, json.data.longitude);
								} else {
									document.getElementById('jform_latitude').value = 0;
									document.getElementById('jform_longitude').value = 0;
								}
							},
							true
						);
					});
				});
				if (window.jQuery) {
					jQuery('#jform_color').change(() => {
						DPCalendar.Map.clearMarkers(map);
						getMarker();
					});
				}
				document.getElementById('jform_color').addEventListener('change', () => {
					DPCalendar.Map.clearMarkers(map);
					getMarker();
				});
				getMarker();
			};
			if (map != null) {
				map.addEventListener('dp-map-loaded', mapLoader);
				watchElements([map]);
			}
			DPCalendar.autocomplete.create(geoComplete);
			geoComplete.addEventListener('dp-autocomplete-select', (e) => {
				let task = 'location.loc';
				if (window.location.href.indexOf('administrator') == -1) {
					task = 'locationform.loc';
				}
				DPCalendar.request(
					'task=' + task + '&loc=' + encodeURIComponent(e.detail.value),
					(json) => {
						if (!json.data) {
							return;
						}
						setGeoResult(json.data);
						if (DPCalendar.Map) {
							DPCalendar.Map.moveMarker(map, getMarker(), json.data.latitude, json.data.longitude);
						}
					},
					true
				);
			});
			geoComplete.addEventListener('dp-autocomplete-change', (e) => {
				let task = 'location.searchloc';
				if (window.location.href.indexOf('administrator') == -1) {
					task = 'locationform.searchloc';
				}
				DPCalendar.request(
					'task=' + task + '&loc=' + encodeURIComponent(e.target.value.trim()),
					(json) => {
						DPCalendar.autocomplete.setItems(geoComplete, json.data);
					},
					true
				);
			});
			geoComplete.parentElement.querySelector('.dp-button-action').addEventListener('click', (e) => {
				e.preventDefault();
				geoComplete.dispatchEvent(new CustomEvent('dp-autocomplete-change', { value: geoComplete.value.trim() }));
				return false;
			});
			[].slice.call(document.querySelectorAll('.com-dpcalendar-locationform__actions .dp-button-action')).forEach((button) => {
				button.addEventListener('click', () => Joomla.submitbutton('locationform.' + button.getAttribute('data-task')));
			});
			Joomla.submitbutton = (task) => {
				const form = document.getElementsByName('adminForm')[0];
				if (form && (task.indexOf('cancel') > -1 || task.indexOf('delete') > -1 || document.formvalidator.isValid(form))) {
					Joomla.submitform(task, form);
				}
			};
		});
	});
	function getAddresString() {
		const getValue = (name) => {
			const tmp = document.getElementById('jform_' + name);
			if (tmp && tmp.tagName === 'SELECT') {
				return tmp.options[tmp.selectedIndex].innerHTML + ', ';
			}
			return tmp ? tmp.value + ', ' : '';
		};
		let street = getValue('street');
		if (street) {
			const number = getValue('number');
			if (number) {
				street = street.substring(0, street.length - 2) + ' ' + number;
			}
		}
		let city = getValue('city');
		if (city) {
			const zip = getValue('zip');
			if (zip) {
				city += city.substring(0, city.length - 2) + ' ' + zip;
			}
		}
		return street + city + getValue('province') + getValue('country');
	}
	function setGeoResult(result) {
		[].slice.call(document.querySelectorAll('.com-dpcalendar-locationform__fields .dp-form-input')).forEach((input) => {
			if (input.id == 'jform_title' || input.id == 'jform_geocomplete') {
				return;
			}
			input.value = '';
		});
		if (document.getElementById('jform_country')) {
			document.getElementById('jform_country').value = result.country;
			internalChange = true;
			document.getElementById('jform_country').dispatchEvent(new Event('change'));
			internalChange = false;
		}
		if (document.getElementById('jform_number')) {
			document.getElementById('jform_number').value = result.number;
		}
		if (document.getElementById('jform_street')) {
			document.getElementById('jform_street').value = result.street;
		}
		if (document.getElementById('jform_city')) {
			document.getElementById('jform_city').value = result.city;
		}
		if (document.getElementById('jform_province')) {
			document.getElementById('jform_province').value = result.province;
		}
		if (document.getElementById('jform_zip')) {
			document.getElementById('jform_zip').value = result.zip;
		}
		document.getElementById('jform_latitude').value = result.latitude;
		document.getElementById('jform_longitude').value = result.longitude;
		if (document.getElementById('jform_title').value == '') {
			document.getElementById('jform_title').value = result.formated;
		}
	}
})();
