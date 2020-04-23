(function () {
	'use strict';

	/**
	 * @package   DPCalendar
	 * @author    Digital Peak http://www.digital-peak.com
	 * @copyright Copyright (C) 2007 - 2020 Digital Peak. All rights reserved.
	 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
	 */

	loadDPAssets(['/com_dpcalendar/js/moment/moment.js'], () => {
		loadDPAssets(['/com_dpcalendar/js/pikaday/pikaday.js', '/com_dpcalendar/css/pikaday/pikaday.css'], () => {
			const names = Joomla.getOptions('DPCalendar.calendar.names');

			moment.updateLocale('en', {
				months: names['monthNames'],
				monthsShort: names['monthNamesShort'],
				weekdays: names['dayNames'],
				weekdaysShort: names['dayNamesShort'],
				weekdaysMin: names['dayNamesMin']
			});

			[].slice.call(document.querySelectorAll('.dp-datepicker')).forEach(function (picker) {
				const element = picker.querySelector('.dp-datepicker__input');
				const options = {trigger: picker.querySelector('.dp-datepicker__button')};
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
					const end = document.getElementById('jform_' + element.getAttribute('data-pair'));
					if (!end || !element.actualDate) {
						return;
					}
					const diff = moment.utc(element.value, options.format).diff(moment.utc(element.actualDate, options.format));
					const date = moment.utc(end.value, options.format);
					date.add(diff, 'ms');
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
//# sourceMappingURL=datepicker.js.map
