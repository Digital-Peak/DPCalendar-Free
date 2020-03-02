(function () {
	'use strict';

	/**
	 * @package   DPCalendar
	 * @author    Digital Peak http://www.digital-peak.com
	 * @copyright Copyright (C) 2007 - 2020 Digital Peak. All rights reserved.
	 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
	 */

	document.addEventListener('DOMContentLoaded', function () {
		[].slice.call(document.querySelectorAll('.com-dpcalendar-invite__actions .dp-button')).forEach(function (button) {
			button.addEventListener('click', function (event) {
				event.preventDefault();

				Joomla.submitbutton('event.' + button.getAttribute('data-task'));
				return false;
			});
		});

		Joomla.submitbutton = function (task) {
			var form = document.getElementsByName('adminForm')[0];
			if (form && (task.indexOf('cancel') > -1 || task.indexOf('delete') > -1 || document.formvalidator.isValid(form))) {
				Joomla.submitform(task, form);
			}
		};

		[].slice.call(document.querySelectorAll('.com-dpcalendar-invite select:not(.dp-timezone__select)')).forEach(function (select) {
			select._choicejs = new Choices(
				select,
				{
					itemSelectText: '',
					noChoicesText: '',
					shouldSortItems: false,
					shouldSort: false,
					removeItemButton: true,
					searchResultLimit: 30
				}
			);
		});
	});

}());
//# sourceMappingURL=default.js.map
