(function () {
	'use strict';

	/**
	 * @package   DPCalendar
	 * @author    Digital Peak http://www.digital-peak.com
	 * @copyright Copyright (C) 2007 - 2020 Digital Peak. All rights reserved.
	 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
	 */

	document.addEventListener('DOMContentLoaded', () => {
		loadDPAssets(['/com_dpcalendar/js/choices/choices.js', '/com_dpcalendar/css/choices/choices.css', '/com_dpcalendar/js/dpcalendar/dpcalendar.js'], () => {
			[].slice.call(document.querySelectorAll('.com-dpcalendar-profile__share .dp-select')).forEach((select) => {
				select._choicejs = new Choices(
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

				select.addEventListener('change', (event) => {
					var data = 'action=' + event.target.name.replace('-users', '') + '&' + event.target.getAttribute('data-token') + '=1&users=';
					select._choicejs.getValue(true).forEach((option) => {
						data += option + ',';
					});

					DPCalendar.request(
						'view=profile&task=profile.change',
						(response) => {
						},
						data
					);
				});
			});
		});

		[].slice.call(document.querySelectorAll('.dp-davcalendar__delete')).forEach((button) => {
			button.addEventListener('click', (e) => {
				if (confirm(Joomla.JText._('COM_DPCALENDAR_CONFIRM_DELETE'))) {
					return true;
				}

				e.preventDefault();
				return false;
			});
		});
	});

}());
//# sourceMappingURL=default.js.map
