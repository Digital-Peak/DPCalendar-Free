/**
 * @package   DPCalendar
 * @copyright Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */
(function () {
	'use strict';
	loadDPAssets(['/com_dpcalendar/js/dayjs/dayjs.js'], () => {
		loadDPAssets(['/com_dpcalendar/js/pikaday/pikaday.js', '/com_dpcalendar/css/pikaday/pikaday.css'], () => {
			const names = Joomla.getOptions('DPCalendar.calendar.names');
			dayjs.updateLocale('en', {
				months: names['monthNames'],
				monthsShort: names['monthNamesShort'],
				weekdays: names['dayNames'],
				weekdaysShort: names['dayNamesShort'],
				weekdaysMin: names['dayNamesMin']
			});
			[].slice.call(document.querySelectorAll('.dp-datepicker')).forEach(picker => {
				const element = picker.querySelector('.dp-datepicker__input');
				const options = {trigger: picker.querySelector('.dp-datepicker__button')};
				options.format = element.getAttribute('data-format');
				options.field = element;
				if (element.getAttribute('data-date')) {
					options.defaultDate = new Date(element.getAttribute('data-date'));
					element.value = dayjs(element.getAttribute('data-date')).format(options.format);
				}
				if (element.getAttribute('data-first-day')) {
					options.firstDay = parseInt(element.getAttribute('data-first-day'));
				}
				options.onSelect = () => {
					const end = document.getElementById('jform_' + element.getAttribute('data-pair'));
					if (!end || !element.actualDate) {
						return;
					}
					const diff = dayjs.utc(element.value, options.format).diff(dayjs.utc(element.actualDate, options.format));
					let date = dayjs.utc(end.value, options.format);
					date = date.add(diff, 'ms');
					end.value = date.format(options.format);
					element.actualDate = element.value;
					element.focus();
				};
				element.actualDate = element.value;
				options.i18n = {
					months: names['monthNames'],
					weekdays: names['dayNames'],
					weekdaysShort: names['dayNamesShort']
				};
				new Pikaday(options);
			});
		});
	});
}());
