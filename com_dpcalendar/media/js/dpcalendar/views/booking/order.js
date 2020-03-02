(function () {
	'use strict';

	/**
	 * @package   DPCalendar
	 * @author    Digital Peak http://www.digital-peak.com
	 * @copyright Copyright (C) 2007 - 2020 Digital Peak. All rights reserved.
	 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
	 */

	document.addEventListener('DOMContentLoaded', function () {
		if (!document.querySelector('.com-dpcalendar-booking__form')) {
			// Form is not available
			return;
		}

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

}());
//# sourceMappingURL=order.js.map
