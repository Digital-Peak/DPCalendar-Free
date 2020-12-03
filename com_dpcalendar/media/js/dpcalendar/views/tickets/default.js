/**
 * @package   DPCalendar
 * @copyright Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */
(function () {
	'use strict';
	document.addEventListener('DOMContentLoaded', () => {
		loadDPAssets(['/com_dpcalendar/js/dpcalendar/dpcalendar.js']);
		document.querySelector('.com-dpcalendar-tickets__actions .dp-input-text').addEventListener('change', (e) => {
			document.querySelector('.com-dpcalendar-tickets__actions .dp-form').submit();
			return false;
		});
		document.querySelector('.com-dpcalendar-tickets__actions .dp-button-search').addEventListener('click', (e) => {
			document.querySelector('.com-dpcalendar-tickets__actions .dp-form').submit();
			return false;
		});
		document.querySelector('.com-dpcalendar-tickets__actions .dp-button-clear').addEventListener('click', (e) => {
			document.querySelector('.com-dpcalendar-tickets .dp-input-text').value = '';
			document.querySelector('.com-dpcalendar-tickets__actions .dp-form').submit();
			return false;
		});
		const checkBox = document.querySelector('.com-dpcalendar-tickets__actions .dp-input-checkbox');
		if (checkBox) {
			checkBox.addEventListener('change', (e) => {
				document.querySelector('.com-dpcalendar-tickets__actions .dp-form').submit();
				return false;
			});
		}
		[].slice.call(document.querySelectorAll('.com-dpcalendar-tickets .dp-toggle')).forEach((toggle) => {
			toggle.addEventListener('click', () => {
				DPCalendar.slideToggle(toggle.nextElementSibling, (fadeIn) => {
					if (!fadeIn) {
						toggle.querySelector('[data-direction="up"]').classList.add('dp-toggle_hidden');
						toggle.querySelector('[data-direction="down"]').classList.remove('dp-toggle_hidden');
						return;
					}
					toggle.querySelector('[data-direction="up"]').classList.remove('dp-toggle_hidden');
					toggle.querySelector('[data-direction="down"]').classList.add('dp-toggle_hidden');
				});
			});
		});
	});
}());
