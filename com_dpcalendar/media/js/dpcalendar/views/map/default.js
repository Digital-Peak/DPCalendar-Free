DPCalendar = window.DPCalendar || {};

(function (document, Joomla, DPCalendar) {
	'use strict';

	document.addEventListener('DOMContentLoaded', function () {
		var update = function (root) {
			DPCalendar.request(
				'view=map&layout=events&format=raw',
				function (json) {
					var map = root.querySelector('.dp-map');
					if (map == null || map.dpmap == null) {
						return;
					}

					DPCalendar.Map.clearMarkers(map.dpmap);

					json.data.events.forEach(function (event) {
						event.location.forEach(function (location) {
							var locationData = JSON.parse(JSON.stringify(location));
							locationData.title = event.title;
							locationData.color = event.color;
							locationData.description = event.description;

							DPCalendar.Map.createMarker(map.dpmap, locationData);
						});
					});

					if (json.data.location && root.querySelector('.dp-input[name=radius]').value != -1) {
						DPCalendar.Map.drawCircle(
							map.dpmap,
							json.data.location,
							root.querySelector('.dp-input[name=radius]').value,
							root.querySelector('.dp-input[name="length-type"]').value
						);
					}
				},
				DPCalendar.formToQueryString(root.querySelector('.dp-form:not(.dp-timezone)'))
			);
		};

		[].slice.call(document.querySelectorAll('.dp-search-map')).forEach(function (map) {
			[].slice.call(map.querySelectorAll('.dp-input, .dp-select:not(.dp-timezone__select)')).forEach(function (input) {
				input.addEventListener('change', function (event) {
					event.preventDefault();

					update(map);

					return false;
				});
			});

			map.addEventListener('click', function (event) {
				if (!event.target || !event.target.matches('.dp-event-tooltip__link')) {
					return true;
				}

				if (window.innerWidth < 600) {
					return true;
				}

				event.preventDefault();

				var root = this.closest('.dp-search-map');
				if (root.dataset.popup == 1) {
					// Opening the modal box
					var url = new Url(event.target.getAttribute('href'));
					url.query.tmpl = 'component';
					DPCalendar.modal(url, root.dataset.popupwidth, root.dataset.popupheight);
				} else {
					window.location = DPCalendar.encode(event.target.getAttribute('href'));
				}
				return false;
			});

			update(map);

			if (DPCalendar.autocomplete) {
				var geoComplete = map.querySelector('.dp-input_location');

				DPCalendar.autocomplete.create(geoComplete);

				geoComplete.addEventListener('dp-autocomplete-select', function (e) {
					update(map);
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

			var button = map.querySelector('.dp-button-search');
			if (button) {
				button.addEventListener('click', function (e) {
					e.preventDefault();

					update(map);

					return false;
				});
			}

			var button = map.querySelector('.dp-button-clear');
			if (button) {
				button.addEventListener('click', function (e) {
					e.preventDefault();

					[].slice.call(map.querySelectorAll('.dp-input:not([name="Itemid"])')).forEach(function (input) {
						input.value = '';
					});


					map.querySelector('[name=radius]').value = 20;
					map.querySelector('[name=length-type]').value = 'm';

					update(map);

					return false;
				});
			}

			var button = map.querySelector('.dp-button-current-location');
			if (button) {
				button.addEventListener('click', function (e) {
					e.preventDefault();

					DPCalendar.currentLocation(function (address) {
						var form = e.target.closest('.dp-form');
						form.querySelector('[name=location]').value = address;
						update(map);
					});

					return false;
				});

				if (!'geolocation' in navigator) {
					button.style.display = 'none';
				}
			}
		});
	});
})(document, Joomla, DPCalendar);
