(function () {
	'use strict';

	/**
	 * @package   DPCalendar
	 * @author    Digital Peak http://www.digital-peak.com
	 * @copyright Copyright (C) 2007 - 2020 Digital Peak. All rights reserved.
	 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
	 */

	document.addEventListener('DOMContentLoaded', function () {
		new Choices(
			'.com-dpcalendar-extcalendar select',
			{
				itemSelectText: '',
				noChoicesText: '',
				shouldSortItems: false,
				shouldSort: false,
				removeItemButton: true,
				searchResultLimit: 30
			}
		);

		Joomla.submitbutton = function (task) {
			var form = document.getElementsByName('adminForm')[0];
			if (form && (task.indexOf('cancel') > -1 || task.indexOf('delete') > -1 || document.formvalidator.isValid(form))) {
				Joomla.submitform(task, form);
			}
		};
	});

}());
//# sourceMappingURL=default.js.map
