(function (document, DPCalendar) {
	'use strict';

	document.addEventListener('DOMContentLoaded', function () {
		[].slice.call(document.querySelectorAll('.dp-timepicker')).forEach(function (element) {
			var format = element.getAttribute('data-format');
			var date = moment(element.getAttribute('data-time'), 'HH:mm');
			element.value = date.format(format);
			element.setAttribute('data-max-length', 0);

			DPCalendar.autocomplete.create(element);

			var minDate = moment(date);
			minDate.set('hour', 0);
			minDate.set('minute', 0);
			minDate.set('second', 0);
			if (element.getAttribute('data-min-time')) {
				var minTime = element.getAttribute('data-min-time').split(':');
				minDate.set('hour', minTime[0]);
				minDate.set('minute', minTime[0]);
			}

			var maxDate = moment(date);
			maxDate.set('hour', 23);
			maxDate.set('minute', 59);
			maxDate.set('second', 0);
			if (element.getAttribute('data-max-time')) {
				var minTime = element.getAttribute('data-max-time').split(':');
				maxDate.set('hour', minTime[0]);
				maxDate.set('minute', minTime[0]);
			}

			var options = [];
			while (minDate < maxDate) {
				options.push({title: minDate.format(format), details: ''});
				minDate.add(element.getAttribute('data-step'), 'minute');
			}

			element.addEventListener('dp-autocomplete-change', function (e) {
				DPCalendar.autocomplete.setItems(element, options);
			});

			element.addEventListener('dp-autocomplete-select', function (e) {
				var event = document.createEvent('HTMLEvents');
				event.initEvent('change');
				element.dispatchEvent(event);
			});

			element.addEventListener('change', function (e) {
				var end = document.getElementById('jform_' + e.target.getAttribute('data-pair'));
				if (!end || !e.target.actualDate) {
					return true;
				}
				var diff = moment.utc(e.target.value, format).diff(moment.utc(e.target.actualDate, format));
				var date = moment.utc(end.value, format);
				date.add(diff, 'ms');
				end.value = date.format(format);
				e.target.actualDate = e.target.value;
			});
			element.actualDate = element.value;

			element.addEventListener('mousedown', function (e) {
				if (element.nextElementSibling) {
					return;
				}

				DPCalendar.autocomplete.setItems(element, options);
				DPCalendar.slideToggle(element.nextElementSibling);
			});
		});
	});
})(document, DPCalendar);
