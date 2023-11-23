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
		loadDPAssets(['/com_dpcalendar/js/dpcalendar/dpcalendar.js'], () => {
			const root = document.querySelector('.com-dpcalendar-list, .com-dpcalendar-blog, .com-dpcalendar-timeline');
			const geoComplete = root.querySelector('.dp-input_location');
			if (geoComplete && geoComplete.dataset.dpAutocomplete == 1) {
				loadDPAssets(['/com_dpcalendar/js/dpcalendar/layouts/block/autocomplete.js'], () => {
					DPCalendar.autocomplete.create(geoComplete);
					geoComplete.addEventListener('dp-autocomplete-select', () => {
						root.querySelector('.dp-form:not(.dp-timezone)').submit();
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
							}
						);
					});
				});
			}
			const map = root.querySelector('.dp-map');
			if (map) {
				watchElements([map]);
				if (geoComplete && geoComplete.getAttribute('data-latitude') && geoComplete.getAttribute('data-longitude')) {
					map.addEventListener('dp-map-loaded', () => {
						DPCalendar.Map.drawCircle(
							map,
							{ latitude: geoComplete.getAttribute('data-latitude'), longitude: geoComplete.getAttribute('data-longitude') },
							root.querySelector('.dp-input[name=radius]').value,
							root.querySelector('.dp-input[name="length-type"]').value
						);
					});
				}
			}
			[].slice.call(root.querySelectorAll('.dp-button-bar__actions .dp-button-search')).forEach((button) => {
				button.addEventListener('click', (event) => {
					event.preventDefault();
					DPCalendar.slideToggle(root.querySelector('.dp-form:not(.dp-timezone)'));
					return false;
				});
			});
			[].slice.call(root.querySelectorAll('.dp-input, .dp-select')).forEach((input) => {
				input.addEventListener('change', () => {
					if (input.name == 'location' && input.nextElementSibling) {
						return;
					}
					if (input.classList.contains('dp-datepicker__input') && !input.dpPikaday) {
						return;
					}
					input.form.submit();
				});
			});
			let button = root.querySelector('.dp-form:not(.dp-timezone) .dp-button-search');
			if (button) {
				button.addEventListener('click', () => {
					root.querySelector('.dp-form:not(.dp-timezone)').submit();
				});
				loadDPAssets(['/com_dpcalendar/js/dpcalendar/layouts/block/datepicker.js', '/com_dpcalendar/js/dpcalendar/layouts/block/timepicker.js']);
			}
			button = root.querySelector('.dp-form:not(.dp-timezone) .dp-button-clear');
			if (button) {
				button.addEventListener('click', (e) => {
					e.preventDefault();
					[].slice.call(root.querySelectorAll('.dp-input:not([name="Itemid"])')).forEach((input) => {
						input.value = '';
					});
					if (geoComplete) {
						root.querySelector('[name=radius]').value = 20;
						root.querySelector('[name=length-type]').value = 'm';
					}
					root.querySelector('.dp-form:not(.dp-timezone)').submit();
					return false;
				});
			}
			button = root.querySelector('.dp-form:not(.dp-timezone) .dp-button-current-location');
			if (button) {
				button.addEventListener('click', (e) => {
					e.preventDefault();
					DPCalendar.currentLocation((address) => {
						const form = e.target.closest('.dp-form:not(.dp-timezone)');
						form.querySelector('[name=location]').value = address;
						form.submit();
					});
					return false;
				});
				if (!('geolocation' in navigator)) {
					button.style.display = 'none';
				}
			}
			const deleteLink = document.querySelector('.dp-event .dp-link_delete');
			if (deleteLink) {
				deleteLink.addEventListener('click', (e) => {
					if (!confirm(Joomla.JText._('COM_DPCALENDAR_CONFIRM_DELETE'))) {
						e.preventDefault();
						return false;
					}
				});
			}
			if (document.body.clientWidth < 768) {
				return;
			}
			let previousEvent = null;
			[].slice.call(root.querySelectorAll('.com-dpcalendar-timeline .dp-event')).forEach((eventElement) => {
				if (previousEvent == null || previousEvent.clientHeight < 300) {
					previousEvent = eventElement;
					return;
				}
				eventElement.querySelector('.dp-event__dot').style.marginTop = '-12rem';
				eventElement.querySelector('.dp-event__information').style.marginTop = '-12rem';
				previousEvent = eventElement;
			});
		});
	});
})();
