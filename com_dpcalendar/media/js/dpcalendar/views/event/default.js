(function () {
	'use strict';

	/**
	 * @package   DPCalendar
	 * @author    Digital Peak http://www.digital-peak.com
	 * @copyright Copyright (C) 2007 - 2020 Digital Peak. All rights reserved.
	 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
	 */

	document.addEventListener('DOMContentLoaded', () => {
		loadDPAssets(['/com_dpcalendar/js/dpcalendar/dpcalendar.js']);

		if (document.querySelector('.com-dpcalendar-event__locations')) {
			loadDPAssets(['/com_dpcalendar/js/dpcalendar/map.js']);
		}

		const mailButton = document.querySelector('.com-dpcalendar-event__actions .dp-button-mail');
		if (mailButton) {
			mailButton.addEventListener('click', (event) => {
				window.open(event.target.getAttribute('data-mailtohref'), 'win2', 'width=400,height=350,menubar=yes,resizable=yes');

				return false;
			});
		}
	});

}());
//# sourceMappingURL=default.js.map
