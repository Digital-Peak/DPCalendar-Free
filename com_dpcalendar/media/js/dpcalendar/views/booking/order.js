/**
 * @package   DPCalendar
 * @copyright Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */
(function () {
	'use strict';
	document.addEventListener('DOMContentLoaded', () => {
		loadDPAssets(['/com_dpcalendar/js/dpcalendar/dpcalendar.js']);
		if (!document.querySelector('.com-dpcalendar-booking__form')) {
			return;
		}
		document.querySelector('.com-dpcalendar-booking__ticket-actions .dp-button-save').addEventListener('click', (event) => {
			event.preventDefault();
			Joomla.submitbutton('ticketform.' + event.target.getAttribute('data-task'));
			return false;
		});
		document.querySelector('.com-dpcalendar-booking__ticket-actions .dp-button-clear').addEventListener('click', (event) => {
			event.preventDefault();
			[].slice.call(document.querySelectorAll('.com-dpcalendar-booking__form input:not([type="hidden"])')).forEach((input) => {
				input.value = '';
			});
			return false;
		});
		Joomla.submitbutton = (task) => {
			const form = document.getElementsByName('adminForm')[0];
			if (form && (task.indexOf('cancel') > -1 || task.indexOf('delete') > -1 || document.formvalidator.isValid(form))) {
				Joomla.submitform(task, form);
			}
		};
	});
}());
