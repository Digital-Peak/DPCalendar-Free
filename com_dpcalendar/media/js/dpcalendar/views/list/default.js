DPCalendar = window.DPCalendar || {};

(function (document, DPCalendar) {
	'use strict';

	document.addEventListener('DOMContentLoaded', function () {
		var root = document.querySelector('.com-dpcalendar-list');
		if (root == null) {
			root = document.querySelector('.com-dpcalendar-blog');
		}
		if (root == null) {
			root = document.querySelector('.com-dpcalendar-timeline');
		}

		var geoComplete = root.querySelector('.dp-input_location');

		if (DPCalendar.autocomplete) {
			DPCalendar.autocomplete.create(geoComplete);

			geoComplete.addEventListener('dp-autocomplete-select', function (e) {
				root.querySelector('.dp-form').submit();
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

		if (DPCalendar.Map && geoComplete.getAttribute('data-latitude') && geoComplete.getAttribute('data-longitude')) {
			DPCalendar.Map.drawCircle(
				root.querySelector('.dp-map').dpmap,
				{latitude: geoComplete.getAttribute('data-latitude'), longitude: geoComplete.getAttribute('data-longitude')},
				root.querySelector('.dp-input[name=radius]').value,
				root.querySelector('.dp-input[name="length-type"]').value
			);
		}

		[].slice.call(root.querySelectorAll('.dp-button-bar__actions .dp-button-search')).forEach(function (button) {
			button.addEventListener('click', function (event) {
				event.preventDefault();

				DPCalendar.slideToggle(root.querySelector('.dp-form'));

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

		var button = root.querySelector('.dp-form .dp-button-search');
		if (button) {
			button.addEventListener('click', function (e) {
				root.querySelector('.dp-form').submit();
			});
		}

		var button = root.querySelector('.dp-form .dp-button-clear');
		if (button) {
			button.addEventListener('click', function (e) {
				e.preventDefault();

				[].slice.call(root.querySelectorAll('.dp-input:not([name="Itemid"])')).forEach(function (input) {
					input.value = '';
				});

				root.querySelector('[name=radius]').value = 20;
				root.querySelector('[name=length-type]').value = 'm';

				root.querySelector('.dp-form').submit();

				return false;
			});
		}

		var button = root.querySelector('.dp-form .dp-button-current-location');
		if (button) {
			button.addEventListener('click', function (e) {
				e.preventDefault();

				DPCalendar.currentLocation(function (address) {
					var form = e.target.closest('.dp-form');
					form.querySelector('[name=location]').value = address;
					form.submit();
				});

				return false;
			});

			if (!'geolocation' in navigator) {
				button.style.display = 'none';
			}
		}
	});
})(document, DPCalendar);
