(function () {
	'use strict';

	/**
	 * @package   DPCalendar
	 * @author    Digital Peak http://www.digital-peak.com
	 * @copyright Copyright (C) 2007 - 2020 Digital Peak. All rights reserved.
	 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
	 */

	document.addEventListener('DOMContentLoaded', function () {
		var elements = document.querySelectorAll('.mod-dpcalendar-upcoming .dp-event-url, .mod-dpcalendar-upcoming .readmore a');
		for (var i = 0; i < elements.length; i++) {
			elements[i].addEventListener('click', function (event) {
				event.preventDefault();

				var url = new Url(this.getAttribute('href'));
				url.query.tmpl = 'component';
				DPCalendar.modal(url, 0, 700);
			});
		}
	});

}());
//# sourceMappingURL=default.js.map
