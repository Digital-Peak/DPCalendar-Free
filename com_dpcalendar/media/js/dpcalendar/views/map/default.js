document.addEventListener("DOMContentLoaded", function () {
	var update = function (root) {
		DPCalendar.loader('hide', root);

		Joomla.request({
			method: 'POST',
			url: 'index.php?option=com_dpcalendar&view=map&layout=events&format=raw',
			data: DPCalendar.formToString(root.querySelector('form')),
			onSuccess: function (response) {
				DPCalendar.loader('hide', root);

				var json = JSON.parse(response);

				var map = root.querySelector('.dpcalendar-fixed-map');
				if (map == null || map.dpmap == null) {
					return;
				}
				DPCalendar.Map.clearMarkers(map.dpmap);

				for (var i = 0; i < json.data.length; i++) {
					var event = json.data[i];
					if (event === null || typeof event !== 'object') {
						continue;
					}

					for (var j = 0; j < event.location.length; j++) {
						var locationData = JSON.parse(JSON.stringify(event.location[j]));
						locationData.title = event.title;
						locationData.color = event.color;
						locationData.description = event.description;

						DPCalendar.Map.createMarker(map.dpmap, locationData);
					}
				}

				if (json.messages != null && document.getElementById('system-message-container')) {
					Joomla.renderMessages(json.messages);
				}
			},
			onError: function (response) {
				DPCalendar.loader('hide', root);

				var json = JSON.parse(response);

				if (json.messages != null && document.getElementById('system-message-container')) {
					Joomla.renderMessages(json.messages);
				}
			}
		});
	}

	var maps = document.querySelectorAll('.dpcalendar-map-container');
	for (var i = 0; i < maps.length; i++) {
		var elements = maps[i].querySelectorAll('input, select');
		for (var j = 0; j < elements.length; j++) {
			elements[j].onchange = function (event) {
				update(this.closest('.dpcalendar-map-container'));
			};
		}

		maps[i].addEventListener('click', function (event) {
			if (!event.target || !event.target.matches('a.dp-event-link')) {
				return true;
			}

			if (window.innerWidth < 600) {
				return true;
			}

			event.preventDefault();

			var root = this.closest('.dpcalendar-map-container');
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

		DPCalendar.loader('hide', maps[i]);

		update(maps[i]);

		var button = maps[i].querySelector('.dp-map-search');
		if (button) {
			button.onclick = function (e) {
				e.preventDefault();

				update(this.closest('.dpcalendar-map-container'));

				return false;
			};
		}

		var button = maps[i].querySelector('.dp-map-cancel');
		if (button) {
			button.onclick = function (e) {
				e.preventDefault();

				var root = this.parentElement.parentElement;

				var inputs = root.querySelectorAll('div>input');
				for (i = 0; i < inputs.length; i++) {
					inputs[i].value = '';
				}

				root.querySelector('[name=radius]').value = 20;

				update(this.closest('.dpcalendar-map-container'));

				return false;
			};
		}

		var button = maps[i].querySelector('.dp-map-current-location');
		if (button) {
			button.onclick = function (e) {
				e.preventDefault();

				DPCalendar.Map.currentLocation(function (address) {
					var root = e.target.closest('.dpcalendar-map-container');
					root.querySelector('[name=location]').value = address;
					update(root);
				});

				return false;
			};
		}

		if (!"geolocation" in navigator) {
			maps[i].querySelector('.dp-map-location').style.display = 'none';
		}
	}
});
