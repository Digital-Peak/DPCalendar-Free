/**
 * @package   DPCalendar
 * @copyright Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */
(function () {
	'use strict';
	document.addEventListener('DOMContentLoaded', () => {
		loadDPAssets(['/com_dpcalendar/js/dpcalendar/dpcalendar.js'], () => {
			DPCalendar.request(
				'task=version.check',
				(json) => {
					if (!json.data.version || json.data.version === '0') {
						return;
					}
					const actualVersion = document.querySelector('.dp-information__version-actual');
					const currentVersion = document.querySelector('.dp-information__version-current');
					if (!actualVersion || !currentVersion) {
						return;
					}
					if (currentVersion.textContent.trim() !== json.data.version) {
						actualVersion.innerHTML = json.data.version;
						actualVersion.parentElement.querySelector('.dp-information__version-update').classList.add('dp-information__version_show');
						actualVersion.classList.add('dp-information__version-actual_new');
						return;
					}
					actualVersion.parentElement.querySelector('.dp-information__version-no-update').classList.add('dp-information__version_show');
				},
				[],
				false,
				'get'
			);
		});
	});
})();
