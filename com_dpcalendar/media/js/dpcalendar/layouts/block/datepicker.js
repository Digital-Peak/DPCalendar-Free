(function (document, Joomla) {
	'use strict';

	document.addEventListener('DOMContentLoaded', function () {
		[].slice.call(document.querySelectorAll('.dp-datepicker')).forEach(function (element) {
			var options = {};
			options.format = element.getAttribute('data-format');
			options.field = element;

			if (element.getAttribute('data-date')) {
				options.defaultDate = new Date(element.getAttribute('data-date'));
				element.value = moment(element.getAttribute('data-date')).format(options.format);
			}

			if (element.getAttribute('data-first-day')) {
				options.firstDay = parseInt(element.getAttribute('data-first-day'));
			}

			options.onSelect = function () {
				var end = document.getElementById('jform_' + element.getAttribute('data-pair'));
				if (!end || !element.actualDate) {
					return;
				}
				var diff = moment.utc(element.value, options.format).diff(moment.utc(element.actualDate, options.format));
				var date = moment.utc(end.value, options.format);
				date.add(diff, 'ms');
				end.value = date.format(options.format);
				element.actualDate = element.value;
				element.focus();
			};
			element.actualDate = element.value;

			var names = Joomla.getOptions('DPCalendar.calendar.names');
			options.i18n = {
				months: names['monthNames'],
				weekdays: names['dayNames'],
				weekdaysShort: names['dayNamesShort']
			};

			new Pikaday(options);
		});
	});
})(document, Joomla);
