(function (document, Joomla, Choices) {
	'use strict';

	document.addEventListener('DOMContentLoaded', function () {
		[].slice.call(document.querySelectorAll('.com-dpcalendar-profile__share .dp-select')).forEach(function (select) {
			var choice = new Choices(select, {
					noResultsText: Joomla.JText._('COM_DPCALENDAR_VIEW_DAVCALENDAR_NONE_SELECTED_LABEL'),
					itemSelectText: '',
					noChoicesText: '',
					shouldSortItems: false,
					shouldSort: false, removeItemButton: true
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
}(document, Joomla, Choices));
