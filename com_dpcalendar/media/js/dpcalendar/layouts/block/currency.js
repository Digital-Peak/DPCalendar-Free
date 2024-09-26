/**
 * @package   DPCalendar
 * @copyright Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */
(function () {
	'use strict';
	document.addEventListener('DOMContentLoaded', () => {
		loadDPAssets(['/com_dpcalendar/js/dpcalendar/layouts/block/select.js']);
		[].slice.call(document.querySelectorAll('.dp-currency__select')).forEach((select) => {
			select.addEventListener('change', () => DPCalendar.request('task=profile.currency&currency=' + select.value, () => location.reload()));
		});
	});
})();
