(function () {
	'use strict';

	/**
	 * @package   DPCalendar
	 * @author    Digital Peak http://www.digital-peak.com
	 * @copyright Copyright (C) 2007 - 2020 Digital Peak. All rights reserved.
	 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
	 */

	document.addEventListener('DOMContentLoaded', () => {
		[].slice.call(document.querySelectorAll('.dp-timezone__select')).forEach((select) => {
			loadDPAssets(['/com_dpcalendar/js/choices/choices.js', '/com_dpcalendar/css/choices/choices.css'], () => {
				select._choicejs = new Choices(
					select,
					{
						itemSelectText: '',
						noChoicesText: '',
						shouldSortItems: false,
						shouldSort: false,
						searchResultLimit: 30
					}
				);
				select.addEventListener('change', () => {
					select.form.submit();
				});
			});
		});
	});

}());
//# sourceMappingURL=timezone.js.map
