(function (document, Joomla) {
	'use strict';

	document.addEventListener('DOMContentLoaded', function () {
		Joomla.submitbutton = function (task) {
			var form = document.getElementsByName('adminForm')[0];
			if (form && (task.indexOf('cancel') > -1 || task.indexOf('delete') > -1 || document.formvalidator.isValid(form))) {
				Joomla.submitform(task, form);
			}
		};
	});
}(document, Joomla));
