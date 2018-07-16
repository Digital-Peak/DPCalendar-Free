(function (document, Joomla) {
	'use strict';

	document.addEventListener('DOMContentLoaded', function () {
		[].slice.call(document.querySelectorAll('.com-dpcalendar-invite__actions .dp-button')).forEach(function (button) {
			button.addEventListener('click', function (event) {
				event.preventDefault();

				Joomla.submitbutton('event.' + this.getAttribute('data-task'));
				return false;
			});
		});

		Joomla.submitbutton = function (task) {
			var form = document.getElementsByName('adminForm')[0];
			if (form && (task.indexOf('cancel') > -1 || task.indexOf('delete') > -1 || document.formvalidator.isValid(form))) {
				Joomla.submitform(task, form);
			}
		};

		[].slice.call(document.querySelectorAll('.com-dpcalendar-invite select')).forEach(function (select) {
			new SlimSelect({select: select});
		});
	});
}(document, Joomla));
