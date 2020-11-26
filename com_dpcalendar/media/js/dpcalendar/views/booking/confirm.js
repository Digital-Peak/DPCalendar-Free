/**
 * @package   DPCalendar
 * @copyright Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */
(function () {
	'use strict';
	document.addEventListener('DOMContentLoaded', () => {
		loadDPAssets(['/com_dpcalendar/js/dpcalendar/dpcalendar.js']);
		document.querySelector('.com-dpcalendar-booking__tickets-header').addEventListener('click', (event) => {
			event.preventDefault();
			DPCalendar.slideToggle(document.querySelector('.com-dpcalendar-booking__tickets'));
			return false;
		});
		const saveButton = document.querySelector('.com-dpcalendar-booking .dp-button-confirm');
		[].slice.call(document.querySelectorAll('.com-dpcalendar-booking .dp-input-term')).forEach((checkbox) => {
			checkbox.addEventListener('change', () => saveButton.disabled = !isSavePossible());
		});
		const options = [].slice.call(document.querySelectorAll('.dp-payment-option'));
		options.forEach((option) => {
			option.addEventListener('click', () => {
				saveButton.disabled = !isSavePossible();
				if (option.querySelector('.dp-input').checked) {
					option.classList.add('dp-payment-option_selected');
				} else {
					option.classList.remove('dp-payment-option_selected');
				}
			});
		});
		saveButton.disabled = !isSavePossible();
		[].slice.call(document.querySelectorAll('.com-dpcalendar-booking__actions .dp-button')).forEach((button) => {
			button.addEventListener('click', (event) => {
				event.preventDefault();
				if (!button.getAttribute('data-task')) {
					return false;
				}
				if (button.getAttribute('data-task') == 'confirm' && !isSavePossible()) {
					return false;
				}
				Joomla.submitbutton('booking.' + button.getAttribute('data-task'));
				return false;
			});
		});
		Joomla.submitbutton = (task) => {
			const form = document.getElementsByName('adminForm')[0];
			if (form && (task.indexOf('cancel') > -1 || task.indexOf('confirm') > -1 || document.formvalidator.isValid(form))) {
				Joomla.submitform(task, form);
			}
		};
	});
	function isSavePossible()
	{
		if (![].slice.call(document.querySelectorAll('.com-dpcalendar-booking .dp-input-term')).every((term) => term.checked)) {
			return false;
		}
		const options = document.querySelectorAll('.dp-payment-option__input');
		if (options.length == 0) {
			return true;
		}
		return [].slice.call(options).some((option) => option.checked);
	}
}());
