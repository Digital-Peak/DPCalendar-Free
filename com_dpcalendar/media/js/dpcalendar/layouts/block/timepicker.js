(function () {
	'use strict';

	/**
	 * @package   DPCalendar
	 * @author    Digital Peak http://www.digital-peak.com
	 * @copyright Copyright (C) 2007 - 2020 Digital Peak. All rights reserved.
	 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
	 */

	loadDPAssets(['/com_dpcalendar/js/moment/moment.js', '/com_dpcalendar/js/dpcalendar/dpcalendar.js', '/com_dpcalendar/js/dpcalendar/layouts/block/autocomplete.js'], () => {
		[].slice.call(document.querySelectorAll('.dp-timepicker')).forEach((element) => {
			const format = element.getAttribute('data-format');
			const date = moment(element.getAttribute('data-time'), 'HH:mm');

			element.value = date.format(format);
			element.setAttribute('data-max-length', 0);

			DPCalendar.autocomplete.create(element);

			const minDate = moment(date);
			minDate.set('hour', 0);
			minDate.set('minute', 0);
			minDate.set('second', 0);
			if (element.getAttribute('data-min-time')) {
				const minTime = element.getAttribute('data-min-time').split(':');
				minDate.set('hour', minTime[0]);
				minDate.set('minute', minTime[0]);
			}

			const maxDate = moment(date);
			maxDate.set('hour', 23);
			maxDate.set('minute', 59);
			maxDate.set('second', 0);
			if (element.getAttribute('data-max-time')) {
				const minTime = element.getAttribute('data-max-time').split(':');
				maxDate.set('hour', minTime[0]);
				maxDate.set('minute', minTime[0]);
			}

			const options = [];
			while (minDate < maxDate) {
				options.push({title: minDate.format(format), details: ''});
				minDate.add(element.getAttribute('data-step'), 'minute');
			}

			element.addEventListener('dp-autocomplete-change', () => {
				DPCalendar.autocomplete.setItems(element, options);
			});

			element.addEventListener('dp-autocomplete-select', () => {
				const event = document.createEvent('HTMLEvents');
				event.initEvent('change');
				element.dispatchEvent(event);
			});

			element.addEventListener('change', (e) => {
				const end = document.getElementById('jform_' + e.target.getAttribute('data-pair'));
				if (!end || !e.target.actualDate) {
					return true;
				}

				const diff = moment.utc(e.target.value, format).diff(moment.utc(e.target.actualDate, format));
				const date = moment.utc(end.value, format);

				date.add(diff, 'ms');
				end.value = date.format(format);
				e.target.actualDate = e.target.value;
			});
			element.actualDate = element.value;

			element.addEventListener('mousedown', () => {
				if (element.nextElementSibling) {
					return;
				}

				DPCalendar.autocomplete.setItems(element, options);
				DPCalendar.slideToggle(element.nextElementSibling);
			});
		});
	});

}());
//# sourceMappingURL=timepicker.js.map
