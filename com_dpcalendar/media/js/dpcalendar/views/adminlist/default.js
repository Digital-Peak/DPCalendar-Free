(function (document, Joomla) {
	'use strict';

	document.addEventListener('DOMContentLoaded', function () {
		Joomla.orderTable = function () {
			var table = document.getElementById('sortTable');
			var direction = document.getElementById('directionTable');
			var order = table.options[table.selectedIndex].value;
			if (order != Joomla.getOptions('DPCalendar.adminlist').listOrder) {
				var dirn = 'asc';
			} else {
				var dirn = direction.options[direction.selectedIndex].value;
			}
			Joomla.tableOrdering(order, dirn, '');
		};

		var check = document.querySelector('.dp-input-check-all');
		if (check) {
			check.addEventListener('click', function (e) {
				Joomla.checkAll(e.target);
			});
		}

		// Events stuff
		var startInput = document.querySelector('input[name="filter[search_start]"]');
		if (startInput) {
			startInput.addEventListener('change', function (e) {
				this.form.submit();
			});
		}
		var endInput = document.querySelector('input[name="filter[search_end]"]');
		if (endInput) {
			endInput.addEventListener('change', function (e) {
				this.form.submit();
			});
		}

		var closeButton = document.querySelector('.com-dpcalendar-events .dp-button-close');
		if (closeButton) {
			closeButton.addEventListener('click', function (e) {
				document.id('batch-category-id').value = '';
				document.id('batch-access').value = '';
				document.id('batch-language-id').value = '';
				document.id('batch-tag-id)').value = '';
			});
		}

		var submitButton = document.querySelector('.com-dpcalendar-events .dp-button-submit');
		if (submitButton) {
			submitButton.addEventListener('click', function (e) {
				Joomla.submitbutton('event.batch');
			});
		}

		[].slice.call(document.querySelectorAll('.com-dpcalendar-events .dp-event .dp-link-featured')).forEach(function (link) {
			link.addEventListener('click', function (e) {
				return listItemTask('cb' + link.getAttribute('data-cb'), link.getAttribute('data-state'));
			});
		});

		[].slice.call(document.querySelectorAll('.com-dpcalendar-events-modal .dp-link')).forEach(function (link) {
			link.addEventListener('click', function (e) {
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

		// Tickets
		[].slice.call(document.querySelectorAll('.js-stools-btn-clear')).forEach(function (button) {
			button.addEventListener('click', function (e) {
				var ticketsEventInput = document.getElementById('filter_event_id_id');
				if (!ticketsEventInput) {
					return
				}
				ticketsEventInput.value = '';
			});
		});
	});
}(document, Joomla));
