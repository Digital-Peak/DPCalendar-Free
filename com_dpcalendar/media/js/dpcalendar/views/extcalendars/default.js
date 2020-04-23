(function () {
	'use strict';

	/**
	 * @package   DPCalendar
	 * @author    Digital Peak http://www.digital-peak.com
	 * @copyright Copyright (C) 2007 - 2020 Digital Peak. All rights reserved.
	 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
	 */

	document.addEventListener('DOMContentLoaded', () => {
		loadDPAssets(['/com_dpcalendar/js/iframe-resizer/iframeresizer-contentwindow.js']);

		Joomla.submitbutton = (task) => {
			if (task == 'plugin.action') {
				document.getElementById('extcalendar-action').val = 'import';
			}
			Joomla.submitform(task, document.getElementById('adminForm'));
		};

		const root = document.querySelector('.com-dpcalendar-extcalendars');
		if (root && root.getAttribute('data-sync') == 2) {
			loadDPAssets(['/com_dpcalendar/js/dpcalendar/dpcalendar.js'], () => {
				DPCalendar.request(
					'task=extcalendars.sync&dpplugin=' + root.getAttribute('data-sync-plugin')
				);
			});
		}
	});

}());
//# sourceMappingURL=default.js.map
