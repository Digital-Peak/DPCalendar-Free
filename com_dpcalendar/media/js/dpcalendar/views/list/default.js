(function () {
	'use strict';

	/**
	 * @package   DPCalendar
	 * @author    Digital Peak http://www.digital-peak.com
	 * @copyright Copyright (C) 2007 - 2020 Digital Peak. All rights reserved.
	 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
	 */

	document.addEventListener('DOMContentLoaded', function () {
		var root = document.querySelector('.com-dpcalendar-list');
		if (root == null) {
			root = document.querySelector('.com-dpcalendar-blog');
		}
		if (root == null) {
			root = document.querySelector('.com-dpcalendar-timeline');
		}

		var geoComplete = root.querySelector('.dp-input_location');
		if (DPCalendar.autocomplete && geoComplete) {
			DPCalendar.autocomplete.create(geoComplete);

			geoComplete.addEventListener('dp-autocomplete-select', function (e) {
				root.querySelector('.dp-form:not(.dp-timezone)').submit();
			});

			geoComplete.addEventListener('dp-autocomplete-change', function (e) {
				var task = 'location.searchloc';
				if (window.location.href.indexOf('administrator') == -1) {
					task = 'locationform.searchloc';
				}
				DPCalendar.request(
					'task=' + task + '&loc=' + encodeURIComponent(e.target.value.trim()),
					function (json) {
						DPCalendar.autocomplete.setItems(geoComplete, json.data);
					}
				);
			});
		}

		if (DPCalendar.Map && geoComplete && geoComplete.getAttribute('data-latitude') && geoComplete.getAttribute('data-longitude')) {
			var map = root.querySelector('.dp-map');
			var circleDrawer = function () {
				DPCalendar.Map.drawCircle(
					map,
					{latitude: geoComplete.getAttribute('data-latitude'), longitude: geoComplete.getAttribute('data-longitude')},
					root.querySelector('.dp-input[name=radius]').value,
					root.querySelector('.dp-input[name="length-type"]').value
				);
			};
			map.addEventListener('dp-map-loaded', circleDrawer);
			if (map.dpmap) {
				circleDrawer();
			}
		}

		[].slice.call(root.querySelectorAll('.dp-button-bar__actions .dp-button-search')).forEach(function (button) {
			button.addEventListener('click', function (event) {
				event.preventDefault();

				DPCalendar.slideToggle(root.querySelector('.dp-form:not(.dp-timezone)'));

				return false;
			});
		});

		[].slice.call(root.querySelectorAll('.dp-input, .dp-select')).forEach(function (input) {
			input.addEventListener('change', function (event) {
				// If autocomplete is activated, do nothing
				if (input.name == 'location' && input.nextElementSibling) {
					return;
				}

				this.form.submit();
			});
		});

		var button = root.querySelector('.dp-form:not(.dp-timezone) .dp-button-search');
		if (button) {
			button.addEventListener('click', function (e) {
				root.querySelector('.dp-form:not(.dp-timezone)').submit();
			});
		}

		var button = root.querySelector('.dp-form:not(.dp-timezone) .dp-button-clear');
		if (button) {
			button.addEventListener('click', function (e) {
				e.preventDefault();

				[].slice.call(root.querySelectorAll('.dp-input:not([name="Itemid"])')).forEach(function (input) {
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

		var button = root.querySelector('.dp-form:not(.dp-timezone) .dp-button-current-location');
		if (button) {
			button.addEventListener('click', function (e) {
				e.preventDefault();

				DPCalendar.currentLocation(function (address) {
					var form = e.target.closest('.dp-form:not(.dp-timezone)');
					form.querySelector('[name=location]').value = address;
					form.submit();
				});

				return false;
			});

			if (!'geolocation' in navigator) {
				button.style.display = 'none';
			}
		}
		if (document.body.clientWidth > 768) {
			var previousEvent = null;
			[].slice.call(root.querySelectorAll('.com-dpcalendar-timeline .dp-event')).forEach(function (eventElement) {
				if (previousEvent == null || previousEvent.clientHeight < 300) {
					previousEvent = eventElement;
					return;
				}

				eventElement.querySelector('.dp-event__dot').style.marginTop = '-200px';
				eventElement.querySelector('.dp-event__information').style.marginTop = '-200px';

				previousEvent = eventElement;
			});
		}
	});

}());
//# sourceMappingURL=default.js.map
