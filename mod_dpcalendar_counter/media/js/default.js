DPCalendar = window.DPCalendar || {};

(function (document, DPCalendar, moment, Url) {
	'use strict';

	document.addEventListener('DOMContentLoaded', function () {
		var elements = document.querySelectorAll('.mod-dpcalendar-counter');

		for (var i = 0; i < elements.length; i++) {
			var element = elements[i];

			if (element.getAttribute('data-modal') == 1) {
				element.addEventListener('click', function (event) {
					if (!event.target || !event.target.matches('.mod-dpcalendar-counter__link')) {
						return;
					}

					event.preventDefault();

					var url = new Url(event.target.getAttribute('href'));
					url.query.tmpl = 'component';
					DPCalendar.modal(url, 0, 700);
				});
			}

			var start = moment(element.getAttribute('data-date'));
			var now = moment();

			if (start - now > 0) {
				element.querySelector('.mod-dpcalendar-counter__ongoing .mod-dpcalendar-counter__intro-text').style.display = 'none';

				var computeDateString = function (type, element, start, now) {
					var diff = start.diff(now, type);
					var key = '';

					if (diff > 0) {
						key = 'MOD_DPCALENDAR_COUNTER_LABEL_' + type.toUpperCase();
						if (diff > 1) {
							key += 'S';
						}
						element.querySelector('.mod-dpcalendar-counter__' + type).classList.remove('dp-counter-block_empty');
					} else {
						diff = '';
						element.querySelector('.mod-dpcalendar-counter__' + type).classList.add('dp-counter-block_empty');
					}

					element.querySelector('.mod-dpcalendar-counter__' + type + ' .dp-counter-block__content').innerText = Joomla.JText._(key);
					element.querySelector('.mod-dpcalendar-counter__' + type + ' .dp-counter-block__number').innerText = diff;

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
				element.querySelector('.mod-dpcalendar-counter__upcoming').style.display = 'none';
			}
		}
	});
})(document, DPCalendar, moment, Url);
