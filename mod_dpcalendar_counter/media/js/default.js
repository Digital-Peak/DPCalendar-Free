/**
 * @package   DPCalendar
 * @copyright Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */
(function () {
	'use strict';
	document.addEventListener('DOMContentLoaded', () => {
		loadDPAssets(['/com_dpcalendar/js/dayjs/dayjs.js'], () => {
			[].slice.call(document.querySelectorAll('.mod-dpcalendar-counter__event')).forEach((element) => {
				if (element.getAttribute('data-modal') == 1) {
					element.addEventListener('click', (event) => {
						if (!event.target || !event.target.matches('.mod-dpcalendar-counter__link')) {
							return;
						}
						event.preventDefault();
						loadDPAssets(['/com_dpcalendar/js/dpcalendar/dpcalendar.js'], () => DPCalendar.modal(event.target.getAttribute('href')));
					});
				}
				const start = dayjs.utc(element.getAttribute('data-date'));
				let now = dayjs.utc();
				if (now.isAfter(start)) {
					element.querySelector('.mod-dpcalendar-counter__upcoming').style.display = 'none';
					return;
				}
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
					return now.add(diff, type);
				};
				now = computeDateString('year', element, start, now);
				now = computeDateString('month', element, start, now);
				now = computeDateString('week', element, start, now);
				now = computeDateString('day', element, start, now);
				now = computeDateString('hour', element, start, now);
				now = computeDateString('minute', element, start, now);
				now = computeDateString('second', element, start, now);
				if (!element.getAttribute('data-counting')) {
					return;
				}
				setInterval((element) => {
					const start = dayjs.utc(element.getAttribute('data-date'));
					let now = dayjs.utc();
					now = computeDateString('year', element, start, now);
					now = computeDateString('month', element, start, now);
					now = computeDateString('week', element, start, now);
					now = computeDateString('day', element, start, now);
					now = computeDateString('hour', element, start, now);
					now = computeDateString('minute', element, start, now);
					now = computeDateString('second', element, start, now);
				}, 1000, element);
			});
		});
	});
})();
