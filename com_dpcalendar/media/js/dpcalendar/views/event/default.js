(function () {
	'use strict';

	/**
	 * @package   DPCalendar
	 * @author    Digital Peak http://www.digital-peak.com
	 * @copyright Copyright (C) 2007 - 2020 Digital Peak. All rights reserved.
	 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
	 */

	document.addEventListener('DOMContentLoaded', () => {
		const mailButton = document.querySelector('.com-dpcalendar-event__actions .dp-button-mail');
		if (mailButton) {
			mailButton.addEventListener('click', (event) => {
				window.open(event.target.getAttribute('data-mailtohref'), 'win2', 'width=400,height=350,menubar=yes,resizable=yes');

				return false;
			});
		}

		[].slice.call(document.querySelectorAll('.com-dpcalendar-event__actions [data-href]')).forEach((action) => {
			action.addEventListener('click', (event) => {
				if (action.getAttribute('data-open') != 'window') {
					location.href = action.getAttribute('data-href');
					return false;
				}

				window.open(action.getAttribute('data-href'));
				return false;
			});
		});
	});

}());
//# sourceMappingURL=default.js.map
