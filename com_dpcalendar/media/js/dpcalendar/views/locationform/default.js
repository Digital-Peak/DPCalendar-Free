DPCalendar = window.DPCalendar || {};

(function (document, Joomla, DPCalendar) {
	'use strict';

	document.addEventListener('DOMContentLoaded', function () {
		var geoComplete = document.getElementById('jform_geocomplete');
		var map = document.querySelector('.dp-map').dpmap;

		var listener = function (e) {
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
		};

		document.getElementById('jform_street').addEventListener('change', listener);
		document.getElementById('jform_number').addEventListener('change', listener);
		document.getElementById('jform_zip').addEventListener('change', listener);
		document.getElementById('jform_city').addEventListener('change', listener);
		document.getElementById('jform_country').addEventListener('change', listener);
		document.getElementById('jform_province').addEventListener('change', listener);

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
					if (json.data) {
						setGeoResult(json.data);

						DPCalendar.Map.moveMarker(map, marker, json.data.latitude, json.data.longitude);
					}
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
		var street = '';
		var city = '';
		var province = '';
		var country = '';
		if (document.getElementById('jform_street').value) {
			street = document.getElementById('jform_street').value;

			if (document.getElementById('jform_number').value) {
				street += ' ' + document.getElementById('jform_number').value;
			}

			street += ', ';
		}
		if (document.getElementById('jform_city').value) {
			city = document.getElementById('jform_city').value;
			if (document.getElementById('jform_zip').value) {
				city += ' ' + document.getElementById('jform_zip').value;
			}

			city += ', ';
		}
		if (document.getElementById('jform_province').value) {
			province = document.getElementById('jform_province').value + ', ';
		}
		if (document.getElementById('jform_country').value) {
			country = document.getElementById('jform_country').value + ', ';
		}
		return street + city + province + country;
	}

	function setGeoResult(result) {
		[].slice.call(document.querySelectorAll('.com-dpcalendar-locationform__fields .dp-form-input')).forEach(function (input) {
			if (input.id == 'jform_title' || input.id == 'jform_geocomplete') {
				return;
			}

			input.value = '';
		});

		document.getElementById('jform_number').value = result.number;
		document.getElementById('jform_street').value = result.street;
		document.getElementById('jform_city').value = result.city;
		document.getElementById('jform_province').value = result.province;
		document.getElementById('jform_country').value = result.country;
		document.getElementById('jform_zip').value = result.zip;

		document.getElementById('jform_latitude').value = result.latitude;
		document.getElementById('jform_longitude').value = result.longitude;

		if (document.getElementById('jform_title').value == '') {
			document.getElementById('jform_title').value = result.formated;
		}
	}

}(document, Joomla, DPCalendar));
