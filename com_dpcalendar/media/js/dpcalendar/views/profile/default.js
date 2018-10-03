(function (document, Joomla, SlimSelect) {
	'use strict';

	document.addEventListener('DOMContentLoaded', function () {
		[].slice.call(document.querySelectorAll('.com-dpcalendar-profile__share .dp-select')).forEach(function (select) {
			var slim = new SlimSelect({select: select, placeholder: Joomla.JText._('COM_DPCALENDAR_VIEW_DAVCALENDAR_NONE_SELECTED_LABEL')});

			select.addEventListener('change', function (event) {
				var data = 'action=' + event.target.name.replace('-users', '') + '&' + event.target.getAttribute('data-token') + '=1&users=';
				slim.selected().forEach(function (option) {
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
}(document, Joomla, SlimSelect));
