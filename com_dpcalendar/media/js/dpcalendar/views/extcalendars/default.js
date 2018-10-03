(function (document, Joomla, DPCalendar) {
	'use strict';

	document.addEventListener('DOMContentLoaded', function () {
		Joomla.submitbutton = function (task) {
			if (task == 'plugin.action') {
				document.getElementById('extcalendar-action').val = 'import';
			}
			Joomla.submitform(task, document.getElementById('adminForm'));
		}
		Joomla.orderTable = function () {
			var table = document.getElementById("sortTable");
			var direction = document.getElementById("directionTable");
			var order = table.options[table.selectedIndex].value;
			if (order != '<?php echo $listOrder; ?>') {
				var dirn = 'asc';
			}
			else {
				var dirn = direction.options[direction.selectedIndex].value;
			}
			Joomla.tableOrdering(order, dirn, '');
		}

		var root = document.querySelector('.com-dpcalendar-extcalendars');
		if (root && root.getAttribute('data-sync') == 2) {
			DPCalendar.request(
				'task=extcalendars.sync&dpplugin=' + root.getAttribute('data-sync-plugin')
			);

		}
	});
}(document, Joomla, DPCalendar));
