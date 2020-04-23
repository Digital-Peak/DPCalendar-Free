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

		document.querySelector('.com-dpcalendar-tickets__actions .dp-input-text').addEventListener('change', (e) => {
			document.querySelector('.com-dpcalendar-tickets .dp-form').submit();

			return false;
		});

		document.querySelector('.com-dpcalendar-tickets__actions .dp-button-search').addEventListener('click', (e) => {
			document.querySelector('.com-dpcalendar-tickets .dp-form').submit();

			return false;
		});

		document.querySelector('.com-dpcalendar-tickets__actions .dp-button-clear').addEventListener('click', (e) => {
			document.querySelector('.com-dpcalendar-tickets .dp-input-text').value = '';
			document.querySelector('.com-dpcalendar-tickets .dp-form').submit();

			return false;
		});

		document.querySelector('.com-dpcalendar-tickets__actions .dp-input-checkbox').addEventListener('change', (e) => {
			document.querySelector('.com-dpcalendar-tickets .dp-form').submit();

			return false;
		});
	});

}());
//# sourceMappingURL=default.js.map
