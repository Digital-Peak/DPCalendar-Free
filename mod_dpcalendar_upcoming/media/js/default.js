(function () {
	'use strict';

	/**
	 * @package   DPCalendar
	 * @author    Digital Peak http://www.digital-peak.com
	 * @copyright Copyright (C) 2007 - 2020 Digital Peak. All rights reserved.
	 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
	 */

	function watchElements(elements)
	{
		elements.forEach((mapElement) => {
			if ('IntersectionObserver' in window === false) {
				loadDPAssets(['/com_dpcalendar/js/dpcalendar/map.js'], () => DPCalendar.Map.create(mapElement));
				return;
			}

			const observer = new IntersectionObserver(
				(entries, observer) => {
					entries.forEach((entry) => {
						if (!entry.isIntersecting) {
							return;
						}
						observer.unobserve(mapElement);

						loadDPAssets(['/com_dpcalendar/js/dpcalendar/map.js'], () => DPCalendar.Map.create(mapElement));
					});
				}
			);
			observer.observe(mapElement);
		});
	}

	/**
	 * @package   DPCalendar
	 * @author    Digital Peak http://www.digital-peak.com
	 * @copyright Copyright (C) 2007 - 2020 Digital Peak. All rights reserved.
	 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
	 */

	document.addEventListener('DOMContentLoaded', () => {
		watchElements(document.querySelectorAll('.mod-dpcalendar-upcoming .dp-map'));

		if (document.querySelector('.mod-dpcalendar-upcoming').getAttribute('data-popup') == 0) {
			return;
		}

		[].slice.call(document.querySelectorAll('.mod-dpcalendar-upcoming .dp-event-url, .mod-dpcalendar-upcoming .readmore a')).forEach((link) => {
			link.addEventListener('click', (event) => {
				event.preventDefault();

				loadDPAssets(['/com_dpcalendar/js/dpcalendar/dpcalendar.js'], () => DPCalendar.modal(link.getAttribute('href')));
			});
		});
	});

}());
//# sourceMappingURL=default.js.map
