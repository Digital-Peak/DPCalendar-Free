(function () {
	'use strict';

	/**
	 * @package   DPCalendar
	 * @author    Digital Peak http://www.digital-peak.com
	 * @copyright Copyright (C) 2007 - 2020 Digital Peak. All rights reserved.
	 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
	 */

	document.addEventListener('DOMContentLoaded', () => {
		[].slice.call(document.querySelectorAll('.com-dpcalendar-davcalendar__actions .dp-button')).forEach((button) => {
			button.addEventListener('click', (event) => {
				event.preventDefault();

				Joomla.submitbutton('davcalendar.' + button.getAttribute('data-task'));
				return false;
			});
		});

		Joomla.submitbutton = (task) => {
			const form = document.getElementsByName('adminForm')[0];
			if (form && (task.indexOf('cancel') > -1 || task.indexOf('delete') > -1 || document.formvalidator.isValid(form))) {
				Joomla.submitform(task, form);
			}
		};
	});

}());
//# sourceMappingURL=default.js.map
