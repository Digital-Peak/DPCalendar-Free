/**
 * @package   DPCalendar
 * @copyright Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */
(function () {
	'use strict';
	function watchElements(elements) {
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
	document.addEventListener('DOMContentLoaded', () => {
		watchElements(document.querySelectorAll('.com-dpcalendar-event__locations .dp-map'));
		loadDPAssets(['/com_dpcalendar/js/dpcalendar/dpcalendar.js'], () => {
			if (!document.querySelector('.com-dpcalendar-event__booking-form')) {
				return;
			}
			document.querySelector('.com-dpcalendar-event__cta .dp-link_cta').addEventListener('click', (e) => {
				e.preventDefault();
				DPCalendar.slideToggle(document.querySelector('.com-dpcalendar-event__booking-form'), (show, element) => {
					if (show) {
						element.classList.remove('dp-toggle_hidden');
					} else {
						element.classList.add('dp-toggle_hidden');
					}
				});
				return false;
			});
		});
		const mailButton = document.querySelector('.com-dpcalendar-event__actions .dp-button-mail');
		if (mailButton) {
			mailButton.addEventListener('click', (event) => {
				window.open(event.target.getAttribute('data-mailtohref'), 'win2', 'width=400,height=350,menubar=yes,resizable=yes');
				return false;
			});
		}
	});
})();
