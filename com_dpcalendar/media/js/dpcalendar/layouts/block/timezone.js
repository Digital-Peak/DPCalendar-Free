/**
 * @package   DPCalendar
 * @copyright Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */
(function () {
	'use strict';
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
})();
