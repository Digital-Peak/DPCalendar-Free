DPCalendar = window.DPCalendar || {};

(function (document, Joomla, DPCalendar) {
	'use strict';

	document.addEventListener('DOMContentLoaded', function () {
		calculatePrice();

		[].slice.call(document.querySelectorAll('.dp-booking-series__input label')).forEach(function (el) {
			calculatePrice();
		});

		[].slice.call(document.querySelectorAll('.dp-booking-series__input input')).forEach(function (input) {
			input.addEventListener('click', function () {
				var showSeries = document.getElementById('jform_series0').checked;
				[].slice.call(document.querySelectorAll('.com-dpcalendar-bookingform__events .dp-event_instance')).forEach(function (row) {
					row.style.display = showSeries ? 'inherit' : 'none'
					row.querySelector('select').value = showSeries ? '1' : '0';
				});

				calculatePrice();
			});
		});

		[].slice.call(document.querySelectorAll('.dp-ticket__amount .dp-select, .dp-option__amount .dp-select')).forEach(function (select) {
			select.addEventListener('change', function () {
				calculatePrice();
			});
		});

		var terms = [].slice.call(document.querySelectorAll('.com-dpcalendar-bookingform__fields .dp-input-terms'));
		terms.forEach(function (checkbox) {
			checkbox.addEventListener('change', function () {
				var accepted = true;
				terms.forEach(function (term) {
					if (!term.checked) {
						accepted = false;
					}
				});

				document.querySelector('.com-dpcalendar-bookingform .dp-button-save').disabled = !accepted;
			});
		});

		if (terms.length) {
			document.querySelector('.com-dpcalendar-bookingform .dp-button-save').disabled = true;
		}

		[].slice.call(document.querySelectorAll('.com-dpcalendar-bookingform__actions .dp-button')).forEach(function (button) {
			button.addEventListener('click', function (event) {
				event.preventDefault();

				var options = document.querySelectorAll('.dp-payment-option');

				// If there is one option check it
				if (this.getAttribute('data-task') == 'save' && options.length == 1) {
					options[0].querySelector('.dp-payment-option__input').checked = true;
				}

				var total = document.querySelector('.dp-price-total__content');
				if (this.getAttribute('data-task') == 'save'
					&& total
					&& total.innerHTML
					&& options.length > 1
					&& !document.querySelectorAll('.dp-payment-option__input:checked').length) {
					DPCalendar.slideToggle(document.querySelector('.com-dpcalendar-bookingform__payment-options'));

					[].slice.call(document.querySelectorAll('.dp-payment-option')).forEach(function (option) {
						option.addEventListener('click', function () {
							this.querySelector('.dp-payment-option__input').checked = true;
							Joomla.submitbutton('bookingform.save');
						});
					});
					return false;
				}

				Joomla.submitbutton('bookingform.' + this.getAttribute('data-task'));
				return false;
			});
		});

		Joomla.submitbutton = function (task) {
			var form = document.getElementsByName('adminForm')[0];
			if (form && (task.indexOf('cancel') > -1 || task.indexOf('delete') > -1 || document.formvalidator.isValid(form))) {
				Joomla.submitform(task, form);
			}
		};
	});

	function calculatePrice() {
		if (!Joomla.getOptions('DPCalendar.price.url') || !document.querySelector('.dp-price-total__content')) {
			return;
		}


		var form = document.querySelector('.com-dpcalendar-bookingform__form');
		var data = DPCalendar.formToQueryString(form, 'input:not([name="task"]), select');
		DPCalendar.request(
			Joomla.getOptions('DPCalendar.price.url'),
			function (json) {
				Object.keys(json.data.events).map(function (eventId) {
					var root = form.querySelector('[data-event-id="' + eventId + '"]');
					Object.keys(json.data.events[eventId]).map(function (type) {
						Object.keys(json.data.events[eventId][type]).map(function (id) {
							var price = json.data.events[eventId][type][id];

							// In markup it is singular
							var selector = type.substring(0, type.length - 1);

							var row = root.querySelector('[data-' + selector + '-price="' + id + '"]');
							if (!row) {
								return;
							}

							var liveCell = row.querySelector('.dp-price__live');
							if (!liveCell) {
								return;
							}

							liveCell.innerHTML = price.discount;
							if (price.discount != price.original) {
								row.querySelector('.dp-price__live').classList.remove('dp-price_hidden');
								row.querySelector('.dp-price__original').classList.remove('dp-price_hidden');
								row.querySelector('.dp-price__original').innerHTML = price.original;
								var info = row.querySelector('.dp-' + selector + '__price-info .dp-icon');
								if (info) {
									info.classList.remove('dp-price_hidden');
								}
							} else {
								row.querySelector('.dp-price__original').classList.add('dp-price_hidden');
								var info = row.querySelector('.dp-' + selector + '__price-info .dp-icon');
								if (info) {
									info.classList.add('dp-price_hidden');
								}
							}
						});
					});
				});

				document.querySelector('.dp-price-total__content').innerHTML = json.data.total;
			},
			data
		);
	};

	DPCalendar.updateBookingMail = function (input) {
		DPCalendar.request(
			'task=booking.mail',
			function (json) {
				if (json.success) {
					document.getElementById('jform_name').value = json.data.name;
					document.getElementById('jform_email').value = json.data.email;
				}
			},
			'ajax=1&id=' + document.getElementById('jform_user_id_id').value
		);
	};
}(document, Joomla, DPCalendar));
