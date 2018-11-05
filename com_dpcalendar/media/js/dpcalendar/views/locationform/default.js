DPCalendar = window.DPCalendar || {};

(function (document, Joomla, DPCalendar) {
	'use strict';

	document.addEventListener('DOMContentLoaded', function () {
		var geoComplete = document.getElementById('jform_geocomplete');
		var map = document.querySelector('.dp-map').dpmap;

		[].slice.call(document.querySelectorAll('#jform_street,#jform_number,#jform_zip,#jform_city,#jform_country,#jform_province')).forEach(function (input) {
			input.addEventListener('change', function (e) {
				geoComplete.value = '';
				var task = 'location.loc';
				if (window.location.href.indexOf('administrator') == -1) {
					task = 'locationform.loc';
				}
				DPCalendar.request(
					'task=' + task + '&loc=' + encodeURIComponent(getAddresString()),
					function (json) {
						if (json.data.latitude) {
							document.getElementById('jform_latitude').value = json.data.latitude;
							document.getElementById('jform_longitude').value = json.data.longitude;

							DPCalendar.Map.moveMarker(map, marker, json.data.latitude, json.data.longitude);
						} else {
							document.getElementById('jform_latitude').value = 0;
							document.getElementById('jform_longitude').value = 0;
						}
					}
				);
			});
		});

		var marker = DPCalendar.Map.createMarker(
			map,
			{
				latitude: document.getElementById('jform_latitude').value,
				longitude: document.getElementById('jform_longitude').value
			},
			function (latitude, longitude) {
				var task = 'location';
				if (window.location.href.indexOf('administrator') == -1) {
					task = 'locationform';
				}
				DPCalendar.request(
					'task=' + task + '.loc&loc=' + latitude + ',' + longitude,
					function (json) {
						if (json.data) {
							setGeoResult(json.data);
						}
					}
				);
			}
		);

		DPCalendar.autocomplete.create(geoComplete);

		geoComplete.addEventListener('dp-autocomplete-select', function (e) {
			var task = 'location.loc';
			if (window.location.href.indexOf('administrator') == -1) {
				task = 'locationform.loc';
			}
			DPCalendar.request(
				'task=' + task + '&loc=' + encodeURIComponent(e.detail.value),
				function (json) {
					if (!json.data) {
						return;
					}
					setGeoResult(json.data);

					if (!marker) {
						marker = DPCalendar.Map.createMarker(
							map,
							{
								latitude: document.getElementById('jform_latitude').value,
								longitude: document.getElementById('jform_longitude').value
							}
						);
					}

					if (!marker) {
						return;
					}

					DPCalendar.Map.moveMarker(map, marker, json.data.latitude, json.data.longitude);
				}
			);
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

		geoComplete.parentElement.querySelector('.dp-button').addEventListener('click', function (e) {
			e.preventDefault();

			var event = document.createEvent('CustomEvent');
			event.initCustomEvent('dp-autocomplete-change', false, false, {value: geoComplete.value.trim()});
			geoComplete.dispatchEvent(event);

			return false;
		});

		[].slice.call(document.querySelectorAll('.com-dpcalendar-locationform__actions .dp-button')).forEach(function (button) {
			button.addEventListener('click', function () {
				Joomla.submitbutton('locationform.' + this.getAttribute('data-task'));
			})
		});

		Joomla.submitbutton = function (task) {
			var form = document.getElementsByName('adminForm')[0];
			if (form && (task.indexOf('cancel') > -1 || task.indexOf('delete') > -1 || document.formvalidator.isValid(form))) {
				Joomla.submitform(task, form);
			}
		};
	});

	function getAddresString() {
		var getValue = function (name) {
			var tmp = document.getElementById('jform_' + name);
			return tmp ? tmp.value + ', ' : '';
		};

		var street = getValue('street');
		if (street) {
			var number = getValue('number');
			if (number) {
				street = street.substr(0, street.length - 2) + number;
			}
		}
		var city = getValue('city');
		if (city) {
			var zip = getValue('zip');
			if (zip) {
				city += city.substr(0, city.length - 2) + zip;
			}
		}

		return street + city + getValue('province') + getValue('country');
	}

	function setGeoResult(result) {
		[].slice.call(document.querySelectorAll('.com-dpcalendar-locationform__fields .dp-form-input')).forEach(function (input) {
			if (input.id == 'jform_title' || input.id == 'jform_geocomplete') {
				return;
			}

			input.value = '';
		});

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
		if (document.getElementById('jform_country')) {
			document.getElementById('jform_country').value = result.country;
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

}(document, Joomla, DPCalendar));
