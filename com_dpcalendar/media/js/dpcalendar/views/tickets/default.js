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
			document.querySelector('.com-dpcalendar-tickets .dp-form').submit();
			return false;
		});
		document.querySelector('.com-dpcalendar-tickets__actions .dp-button-search').addEventListener('click', (e) => {
			document.querySelector('.com-dpcalendar-tickets .dp-form').submit();
			return false;
		});
		document.querySelector('.com-dpcalendar-tickets__actions .dp-button-clear').addEventListener('click', (e) => {
			document.querySelector('.com-dpcalendar-tickets .dp-input-text').value = '';
			document.querySelector('.com-dpcalendar-tickets .dp-form').submit();
			return false;
		});
		const checkBox = document.querySelector('.com-dpcalendar-tickets__actions .dp-input-checkbox');
		if (checkBox) {
			checkBox.addEventListener('change', (e) => {
				document.querySelector('.com-dpcalendar-tickets .dp-form').submit();
				return false;
			});
		}
	});
}());
