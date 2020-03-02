(function () {
	'use strict';

	/**
	 * @package   DPCalendar
	 * @author    Digital Peak http://www.digital-peak.com
	 * @copyright Copyright (C) 2007 - 2020 Digital Peak. All rights reserved.
	 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
	 */

	document.addEventListener('DOMContentLoaded', function () {
		document.querySelector('.com-dpcalendar-tickets__actions .dp-input-text').addEventListener('change', function () {
			this.form.submit();

			return false;
		});
		document.querySelector('.com-dpcalendar-tickets__actions .dp-button-search').addEventListener('click', function () {
			this.form.submit();

			return false;
		});
		document.querySelector('.com-dpcalendar-tickets__actions .dp-button-clear').addEventListener('click', function () {
			this.parentElement.parentElement.querySelector('.dp-input-text').value = '';
			this.form.submit();

			return false;
		});
		document.querySelector('.com-dpcalendar-tickets__actions .dp-input-checkbox').addEventListener('change', function () {
			this.form.submit();

			return false;
		});
	});

}());
//# sourceMappingURL=default.js.map
