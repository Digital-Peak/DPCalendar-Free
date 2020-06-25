(function () {
	'use strict';

	/**
	 * @package   DPCalendar
	 * @author    Digital Peak http://www.digital-peak.com
	 * @copyright Copyright (C) 2007 - 2020 Digital Peak. All rights reserved.
	 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
	 */

	document.addEventListener('DOMContentLoaded', () => {
		loadDPAssets(['/com_dpcalendar/js/moment/moment.js'], () => {
			[].slice.call(document.querySelectorAll('.mod-dpcalendar-counter')).forEach((element) => {
				if (element.getAttribute('data-modal') == 1) {
					element.addEventListener('click', (event) => {
						if (!event.target || !event.target.matches('.mod-dpcalendar-counter__link')) {
							return;
						}

						event.preventDefault();

						loadDPAssets(['/com_dpcalendar/js/dpcalendar/dpcalendar.js'], () => {
							DPCalendar.modal(event.target.getAttribute('href'));
						});
					});
				}

				const start = moment(element.getAttribute('data-date'));
				const now = moment();

				if (start - now > 0) {
					element.querySelector('.mod-dpcalendar-counter__ongoing .mod-dpcalendar-counter__intro-text').style.display = 'none';

					const computeDateString = (type, element, start, now) => {
						const root = element.querySelector('.mod-dpcalendar-counter__' + type);
						if (root == null) {
							return;
						}

						let diff = start.diff(now, type);
						let key = '';

						if (diff > 0 || type == 'second') {
							key = 'MOD_DPCALENDAR_COUNTER_LABEL_' + type.toUpperCase();
							if (diff > 1) {
								key += 'S';
							}
							root.classList.remove('dp-counter-block_empty');
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
						setInterval((element) => {
							const start = moment(element.getAttribute('data-date'));
							const now = moment();
							computeDateString('year', element, start, now);
							computeDateString('month', element, start, now);
							computeDateString('week', element, start, now);
							computeDateString('day', element, start, now);
							computeDateString('hour', element, start, now);
							computeDateString('minute', element, start, now);
							computeDateString('second', element, start, now);
						}, 1000, element);
					}
				} else {
					element.querySelector('.mod-dpcalendar-counter__upcoming').style.display = 'none';
				}
			});
		});
	});

}());
//# sourceMappingURL=default.js.map
