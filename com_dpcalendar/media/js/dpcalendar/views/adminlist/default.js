/**
 * @package   DPCalendar
 * @copyright Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */
(function () {
	'use strict';
	document.addEventListener('DOMContentLoaded', () => {
		Joomla.orderTable = () => {
			const table = document.getElementById('sortTable');
			const direction = document.getElementById('directionTable');
			const order = table.options[table.selectedIndex].value;
			let dirn = 'asc';
			if (order == Joomla.getOptions('DPCalendar.adminlist').listOrder) {
				dirn = direction.options[direction.selectedIndex].value;
			}
			Joomla.tableOrdering(order, dirn, '');
		};
		Joomla.submitbutton = (task) => {
			const form = document.getElementsByName('adminForm')[0];
			Joomla.submitform(task, form);
			form.task.value = '';
		};
		const startInput = document.querySelector('input[name="filter[search_start]"]');
		if (startInput) {
			startInput.addEventListener('change', () => {
				if (startInput.classList.contains('dp-datepicker__input') && !startInput.dpPikaday) {
					return;
				}
				endInput.form.submit();
			});
			loadDPAssets(['/com_dpcalendar/js/dpcalendar/layouts/block/datepicker.js', '/com_dpcalendar/js/dpcalendar/layouts/block/timepicker.js']);
		}
		Array.from(document.querySelectorAll('.dp-event__state div[role="tooltip"]')).forEach((state) => state.remove());
		const endInput = document.querySelector('input[name="filter[search_end]"]');
		if (endInput) {
			endInput.addEventListener('change', () => {
				if (endInput.classList.contains('dp-datepicker__input') && !endInput.dpPikaday) {
					return;
				}
				endInput.form.submit();
			});
		}
		const closeButton = document.querySelector('.com-dpcalendar-events .dp-button-close');
		if (closeButton) {
			closeButton.addEventListener('click', () => {
				document.getElementById('batch-category-id').value = '';
				document.getElementById('batch-access').value = '';
				document.getElementById('batch-language-id').value = '';
				document.getElementById('batch-tag-id').value = '';
			});
		}
		const submitButton = document.querySelector('.com-dpcalendar-events .dp-button-submit');
		if (submitButton) {
			submitButton.addEventListener('click', () => Joomla.submitbutton('event.batch'));
		}
		[].slice.call(document.querySelectorAll('.com-dpcalendar-events .dp-event .dp-link-featured')).forEach((link) => {
			link.addEventListener('click', () => Joomla.listItemTask('cb' + link.getAttribute('data-cb'), link.getAttribute('data-state')));
		});
		[].slice.call(document.querySelectorAll('.com-dpcalendar-events-modal .dp-link')).forEach((link) => {
			link.addEventListener('click', (e) => {
				if (!window.parent) {
					return;
				}
				window.parent[e.target.getAttribute('data-function')](
					e.target.getAttribute('data-id'),
					e.target.getAttribute('data-title'),
					e.target.getAttribute('data-catid'),
					null,
					e.target.getAttribute('data-url'),
					'null',
					null
				);
			});
		});
		[].slice.call(document.querySelectorAll('.js-stools-btn-clear')).forEach((button) => {
			button.addEventListener('click', () => {
				const ticketsEventInput = document.getElementById('filter_event_id_id');
				if (!ticketsEventInput) {
					return;
				}
				ticketsEventInput.value = '';
			});
		});
		const eventFilter = document.getElementById('filter_event_id_id');
		if (eventFilter) {
			eventFilter.addEventListener('change', (e) => e.target.form.submit());
		}
	});
})();
