document.addEventListener("DOMContentLoaded", function () {
	var elements = document.querySelectorAll('.dp-module-counter-root');

	for (var i = 0; i < elements.length; i++) {
		var element = elements[i];

		if (element.getAttribute('data-modal')) {
			element.addEventListener('click', function (event) {
				if (!event.target || !event.target.matches("a.dp-module-counter-link")) {
					return;
				}

				event.preventDefault();

				var url = new Url(this.getAttribute('href'));
				url.query.tmpl = 'component';
				DPCalendar.modal(url, 0, 700);
			});
		}

		var start = moment(element.getAttribute('data-date'));
		var now = moment();

		if (start - now > 0) {
			element.querySelector('.dp-module-counter-ongoing').style.display = 'none';

			var computeDateString = function (type, element, start, now) {
				var diff = start.diff(now, type);
				var key = '';

				if (diff > 0) {
					key = 'MOD_DPCALENDAR_COUNTER_LABEL_' + type.toUpperCase();
					if (diff > 1) {
						key += 'S';
					}
					element.querySelector('.dp-module-counter-' + type).classList.remove('dp-module-counter-empty-cell');
				} else {
					diff = '';
					element.querySelector('.dp-module-counter-' + type).classList.add('dp-module-counter-empty-cell');
				}

				element.querySelector('.dp-module-counter-' + type + '-content').innerText = Joomla.JText._(key);
				element.querySelector('.dp-module-counter-' + type + '-number').innerText = diff;

				now.add(diff, type);
			};

			computeDateString('year', element, start, now);
			computeDateString('month', element, start, now);
			computeDateString('week', element, start, now);
			computeDateString('day', element, start, now);
			computeDateString('hour', element, start, now);
			computeDateString('minute', element, start, now);
			computeDateString('second', element, start, now);

			if (element.getAttribute('data-counting')) {
				setInterval(function (element) {
					var start = moment(element.getAttribute('data-date'));
					var now = moment();
					computeDateString('year', element, start, now);
					computeDateString('month', element, start, now);
					computeDateString('week', element, start, now);
					computeDateString('day', element, start, now);
					computeDateString('hour', element, start, now);
					computeDateString('minute', element, start, now);
					computeDateString('second', element, start, now);
				}, 1000, element);
			}
		}
		else {
			element.querySelector('.dp-module-counter-container').style.display = 'none';
		}
	}
});
