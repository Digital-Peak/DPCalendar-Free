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
		loadDPAssets(['/com_dpcalendar/js/dpcalendar/calendar.js']);
		watchElements([].slice.call(document.querySelectorAll('.com-dpcalendar-calendar__map')));
		const noLink = document.querySelector('.com-dpcalendar-calendar_printable');
		if (noLink) {
			setInterval(() => {
				[].slice.call(noLink.querySelectorAll('a')).forEach((link) => link.removeAttribute('href'));
			}, 2000);
		}
		const quickAdds = [].slice.call(document.querySelectorAll('.dp-quickadd'));
		if (quickAdds.length === 0) {
			return;
		}
		loadDPAssets(['/com_dpcalendar/js/dpcalendar/layouts/block/datepicker.js', '/com_dpcalendar/js/dpcalendar/layouts/block/timepicker.js']);
		document.onkeydown = (evt) => {
			const event = evt || window.event;
			let isEscape = false;
			if ('key' in evt) {
				isEscape = (event.key == 'Escape' || event.key == 'Esc');
			} else {
				isEscape = (event.keyCode == 27);
			}
			if (isEscape) {
				quickAdds.forEach((quickAdd) => quickAdd.style.display = 'none');
			}
		};
		document.addEventListener('click', (event) => {
			quickAdds.forEach((quickAdd) => {
				if (quickAdd.contains(event.target) || event.target.classList.contains('dp-autocomplete__item-title')) {
					return;
				}
				quickAdd.style.display = 'none';
			});
		});
		quickAdds.forEach((quickAdd) => {
			window.addEventListener('hashchange', () => quickAdd.querySelector('input[name=urlhash]').value = window.location.hash);
			quickAdd.querySelector('input[name=urlhash]').value = window.location.hash;
			const buttons = [].slice.call(quickAdd.querySelectorAll('.dp-quickadd__buttons .dp-button'));
			quickAdd.querySelector('.dp-quickadd__button-submit').addEventListener('click', (e) => {
				e.preventDefault();
				buttons.forEach((button) => button.disabled = true);
				quickAdd.querySelector('input[name=task]').value = 'event.saveajax';
				const form = quickAdd.querySelector('.dp-form');
				DPCalendar.request(
					form.action.substring(form.action.indexOf('?')),
					(json) => {
						if (json.success) {
							quickAdd.parentElement.querySelector(':scope > .dp-calendar').dpCalendar.refetchEvents();
							quickAdd.querySelector('input[name="jform[title]"]').value = '';
							quickAdd.style.display = 'none';
						}
						buttons.forEach((button) => button.disabled = false);
					},
					DPCalendar.formToQueryString(form),
					true,
					null,
					() => buttons.forEach((button) => button.disabled = false)
				);
				return false;
			});
			quickAdd.querySelector('.dp-quickadd__button-edit').addEventListener('click', () => {
				buttons.forEach((button) => button.disabled = true);
				quickAdd.querySelector('.dp-form').submit();
			});
			quickAdd.querySelector('.dp-quickadd__button-cancel').addEventListener('click', () => {
				quickAdd.querySelector('input[name="jform[title]"]').value = '';
				quickAdd.style.display = 'none';
			});
		});
	});
})();
