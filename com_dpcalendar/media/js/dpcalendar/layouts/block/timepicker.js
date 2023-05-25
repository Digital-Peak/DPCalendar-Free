/**
 * @package   DPCalendar
 * @copyright Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */
(function () {
	'use strict';
	loadDPAssets(['/com_dpcalendar/js/dayjs/dayjs.js', '/com_dpcalendar/js/dpcalendar/dpcalendar.js', '/com_dpcalendar/js/dpcalendar/layouts/block/autocomplete.js'], () => {
		[].slice.call(document.querySelectorAll('.dp-timepicker')).forEach((element) => {
			const format = element.getAttribute('data-format');
			const date = dayjs(element.getAttribute('data-time'), 'HH:mm');
			const size = date.format(format).length + 1;
			element.value = date.format(format);
			element.setAttribute('data-max-length', 0);
			DPCalendar.autocomplete.create(element);
			let minDate = dayjs(date);
			minDate = minDate.set('hour', 0);
			minDate = minDate.set('minute', 0);
			minDate = minDate.set('second', 0);
			if (element.getAttribute('data-min-time')) {
				const minTime = element.getAttribute('data-min-time').split(':');
				minDate = minDate.set('hour', minTime[0]);
				minDate = minDate.set('minute', minTime[1]);
			}
			let maxDate = dayjs(date);
			maxDate = maxDate.set('hour', 23);
			maxDate = maxDate.set('minute', 59);
			maxDate = maxDate.set('second', 0);
			if (element.getAttribute('data-max-time')) {
				const maxTime = element.getAttribute('data-max-time').split(':');
				maxDate = maxDate.set('hour', maxTime[0]);
				maxDate = maxDate.set('minute', maxTime[1]);
			}
			const options = [];
			while (minDate.isBefore(maxDate)) {
				options.push({ title: minDate.format(format), details: '' });
				minDate = minDate.add(element.getAttribute('data-step'), 'minute');
			}
			element.addEventListener('dp-autocomplete-change', () => DPCalendar.autocomplete.setItems(element, options));
			element.addEventListener('dp-autocomplete-select', () => element.dispatchEvent(new Event('change')));
			element.addEventListener('change', (e) => {
				const end = document.getElementById('jform_' + e.target.getAttribute('data-pair'));
				if (!end || !e.target.actualDate || !e.target.value) {
					return true;
				}
				const diff = dayjs.utc(e.target.value, format).diff(dayjs.utc(e.target.actualDate, format));
				let date = dayjs.utc(end.value, format);
				date = date.add(diff, 'ms');
				end.value = date.format(format);
				e.target.actualDate = e.target.value;
			});
			element.actualDate = element.value;
			const style = window.getComputedStyle(element);
			element.style.width = 'calc(' + size + 'ch + ' + style.paddingLeft + ' + ' + style.paddingRight + ')';
			element.addEventListener('mousedown', () => DPCalendar.autocomplete.setItems(element, options));
			element.addEventListener('focus', () => DPCalendar.autocomplete.setItems(element, options));
		});
	});
})();
