(function () {
	'use strict';

	/**
	 * @package   DPCalendar
	 * @author    Digital Peak http://www.digital-peak.com
	 * @copyright Copyright (C) 2007 - 2020 Digital Peak. All rights reserved.
	 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
	 */

	document.addEventListener('DOMContentLoaded', function () {
		[].slice.call(document.querySelectorAll('.com-dpcalendar-profile__share .dp-select')).forEach(function (select) {
			var choice = new Choices(
				select,
				{
					noResultsText: Joomla.JText._('COM_DPCALENDAR_VIEW_DAVCALENDAR_NONE_SELECTED_LABEL'),
					itemSelectText: '',
					noChoicesText: '',
					shouldSortItems: false,
					shouldSort: false,
					removeItemButton: true,
					searchResultLimit: 30
				}
			);

			select.addEventListener('change', function (event) {
				var data = 'action=' + event.target.name.replace('-users', '') + '&' + event.target.getAttribute('data-token') + '=1&users=';
				choice.getValue(true).forEach(function (option) {
					data += option + ',';
				});

				DPCalendar.request(
					'view=profile&task=profile.change',
					function (response) {
					},
					data
				);
			});
		});

		document.querySelector('.dp-davcalendar__delete').addEventListener('click', function (e) {
			if (confirm(Joomla.JText._('COM_DPCALENDAR_CONFIRM_DELETE'))) {
				return true;
			}

			e.preventDefault();
			return false;
		});
	});

}());
//# sourceMappingURL=default.js.map
