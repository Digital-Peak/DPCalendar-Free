(function (document, Joomla) {
	'use strict';

	document.addEventListener('DOMContentLoaded', function () {
		document.querySelector('.com-dpcalendar-booking__ticket-actions .dp-button-save').addEventListener('click', function (event) {
			event.preventDefault();

			Joomla.submitbutton('ticketform.' + this.getAttribute('data-task'));
			return false;
		});
		document.querySelector('.com-dpcalendar-booking__ticket-actions .dp-button-clear').addEventListener('click', function (event) {
			event.preventDefault();

			[].slice.call(document.querySelectorAll('.com-dpcalendar-booking__form input:not([type="hidden"])')).forEach(function (input) {
				input.value = '';
			});
			return false;
		});

		Joomla.submitbutton = function (task) {
			var form = document.getElementsByName('adminForm')[0];
			if (form && (task.indexOf('cancel') > -1 || task.indexOf('delete') > -1 || document.formvalidator.isValid(form))) {
				Joomla.submitform(task, form);
			}
		};
	});
}(document, Joomla));
