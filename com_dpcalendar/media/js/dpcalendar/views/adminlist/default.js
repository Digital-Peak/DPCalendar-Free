/**
 * @package   DPCalendar
 * @copyright Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
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
		const check = document.querySelector('.dp-input-check-all');
		if (check) {
			check.addEventListener('click', (e) => {
				Joomla.checkAll(e.target);
			});
		}
		const startInput = document.querySelector('input[name="filter[search_start]"]');
		if (startInput) {
			startInput.addEventListener('change', (e) => {
				startInput.form.submit();
			});
			loadDPAssets(['/com_dpcalendar/js/dpcalendar/layouts/block/datepicker.js', '/com_dpcalendar/js/dpcalendar/layouts/block/timepicker.js']);
		}
		const endInput = document.querySelector('input[name="filter[search_end]"]');
		if (endInput) {
			endInput.addEventListener('change', (e) => {
				endInput.form.submit();
			});
		}
		const closeButton = document.querySelector('.com-dpcalendar-events .dp-button-close');
		if (closeButton) {
			closeButton.addEventListener('click', (e) => {
				document.getElementById('batch-category-id').value = '';
				document.getElementById('batch-access').value = '';
				document.getElementById('batch-language-id').value = '';
				document.getElementById('batch-tag-id').value = '';
			});
		}
		const submitButton = document.querySelector('.com-dpcalendar-events .dp-button-submit');
		if (submitButton) {
			submitButton.addEventListener('click', (e) => {
				Joomla.submitbutton('event.batch');
			});
		}
		[].slice.call(document.querySelectorAll('.com-dpcalendar-events .dp-event .dp-link-featured')).forEach((link) => {
			link.addEventListener('click', (e) => Joomla.listItemTask('cb' + link.getAttribute('data-cb'), link.getAttribute('data-state')));
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
			button.addEventListener('click', (e) => {
				const ticketsEventInput = document.getElementById('filter_event_id_id');
				if (!ticketsEventInput) {
					return
				}
				ticketsEventInput.value = '';
			});
		});
	});
})();
