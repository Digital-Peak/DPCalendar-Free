/**
 * @package   DPCalendar
 * @copyright Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */
(function () {
	'use strict';
	document.addEventListener('DOMContentLoaded', () => {
		loadDPAssets(['/com_dpcalendar/js/dpcalendar/layouts/block/select.js']);
		[].slice.call(document.querySelectorAll('.dp-timezone__select')).forEach((select) => {
			select.addEventListener('change', () => DPCalendar.request('task=profile.tz&tz=' + select.value, () => location.reload()));
			if (localStorage.getItem('DPCalendar.timezone.switcher.disable') == 1) {
				return;
			}
			if (Intl.DateTimeFormat().resolvedOptions().timeZone === select.value) {
				return;
			}
			const notification = document.querySelector('.dp-timezone__info');
			if (!notification) {
				return;
			}
			notification.innerHTML = notification.innerHTML.replace('%s', Intl.DateTimeFormat().resolvedOptions().timeZone);
			notification.classList.remove('dp-timezone__info_hidden');
			notification.querySelector('.dp-link_confirm').addEventListener('click', (e) => {
				e.preventDefault();
				select.value = Intl.DateTimeFormat().resolvedOptions().timeZone;
				select.dispatchEvent(new Event('change'));
				return false;
			});
			notification.querySelector('.dp-link_close').addEventListener('click', (e) => {
				e.preventDefault();
				notification.classList.add('dp-timezone__info_hidden');
				localStorage.setItem('DPCalendar.timezone.switcher.disable', 1);
				return false;
			});
		});
	});
})();
