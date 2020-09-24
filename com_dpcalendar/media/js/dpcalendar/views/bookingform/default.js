(function () {
	'use strict';

	/**
	 * @package   DPCalendar
	 * @author    Digital Peak http://www.digital-peak.com
	 * @copyright Copyright (C) 2007 - 2020 Digital Peak. All rights reserved.
	 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
	 */

	document.addEventListener('DOMContentLoaded', () => {
		[].slice.call(document.querySelectorAll('.dp-booking-series__input input')).forEach((input) => {
			input.addEventListener('click', () => {
				const showSeries = document.getElementById('jform_series0').checked;
				[].slice.call(document.querySelectorAll('.com-dpcalendar-bookingform__events .dp-event_instance')).forEach((row) => {
					row.style.display = showSeries ? 'inherit' : 'none';
					row.querySelector('select').value = showSeries ? '1' : '0';
				});

				calculatePrice();
			});
		});

		[].slice.call(document.querySelectorAll('.dp-ticket__amount .dp-select, .dp-option__amount .dp-select, .dp-field-country .dp-select')).forEach((select) => {
			select.addEventListener('change', calculatePrice);
		});

		const saveButton = document.querySelector('.com-dpcalendar-bookingform .dp-button-save');
		if (saveButton) {
			[].slice.call(document.querySelectorAll('.com-dpcalendar-bookingform__form .dp-input-term')).forEach((checkbox) => {
				checkbox.addEventListener('change', () => saveButton.disabled = !isSavePossible());
			});

			saveButton.disabled = !isSavePossible();
		}

		calculatePrice();

		[].slice.call(document.querySelectorAll('.com-dpcalendar-bookingform__actions .dp-button')).forEach((button) => {
			button.addEventListener('click', (event) => {
				event.preventDefault();

				const options = document.querySelectorAll('.dp-payment-option');

				if (!isSavePossible()) {
					return;
				}

				// If there is one option check it
				if (button.getAttribute('data-task') == 'save' && options.length == 1) {
					options[0].querySelector('.dp-payment-option__input').checked = true;
				}

				// If there is no price check the first option
				const total = document.querySelector('.dp-price-total__content');
				if (button.getAttribute('data-task') == 'save' && options.length > 0 && total && total.getAttribute('data-raw') == 0) {
					options[0].querySelector('.dp-payment-option__input').checked = true;
				}

				if (button.getAttribute('data-task') == 'save'
					&& options.length > 1
					&& !document.querySelectorAll('.dp-payment-option__input:checked').length) {
					const form = document.getElementsByName('adminForm')[0];
					if (form && !document.formvalidator.isValid(form)) {
						return false;
					}

					if (saveButton) {
						saveButton.disabled = true;
					}

					DPCalendar.slideToggle(document.querySelector('.com-dpcalendar-bookingform__payment-options'));

					[].slice.call(document.querySelectorAll('.dp-payment-option')).forEach((option) => {
						option.addEventListener('click', () => {
							option.querySelector('.dp-payment-option__input').checked = true;
							Joomla.submitbutton('bookingform.save');
						});
					});
					return false;
				}

				Joomla.submitbutton('bookingform.' + button.getAttribute('data-task'));
				return false;
			});
		});

		Joomla.submitbutton = (task) => {
			const form = document.getElementsByName('adminForm')[0];
			if (form && (task.indexOf('cancel') > -1 || task.indexOf('delete') > -1 || document.formvalidator.isValid(form))) {
				Joomla.submitform(task, form);
			}
		};

		[].slice.call(document.querySelectorAll('.com-dpcalendar-bookingform .dp-field-country .dp-select')).forEach((select) => {
			loadDPAssets(['/com_dpcalendar/js/choices/choices.js', '/com_dpcalendar/css/choices/choices.css'], () => {
				select._choicejs = new Choices(
					select,
					{
						itemSelectText: '',
						noChoicesText: '',
						shouldSortItems: false,
						shouldSort: false,
						searchResultLimit: 30
					}
				);
			});
		});

		const userField = document.getElementById('jform_user_id');
		if (!userField) {
			return;
		}

		const userUpdater = () => {
			if (document.getElementById('jform_id').value > 0) {
				return;
			}

			loadDPAssets(['/com_dpcalendar/js/dpcalendar/dpcalendar.js'], () => {
				DPCalendar.request(
					'task=booking.mail',
					(json) => {
						if (!json.success) {
							return;
						}

						const fill = (name) => {
							if (!json.data[name]) {
								return;
							}

							const el = document.getElementById('jform_' + name);
							if (!el) {
								return;
							}
							el.value = json.data[name];
						};
						fill('name');
						fill('email');
						fill('province');
						fill('city');
						fill('zip');
						fill('street');
						fill('number');
						fill('telephone');
						fill('latitude');
						fill('longitude');

						if (json.data.country) {
							document.getElementById('jform_country')._choicejs._presetChoices.forEach((item) => {
								if (item.label == json.data.country) {
									document.getElementById('jform_country')._choicejs.setChoiceByValue(item.value);
								}
							});
						}
					},
					'ajax=1&id=' + document.getElementById('jform_user_id_id').value
				);
			});
		};

		userField.addEventListener('change', userUpdater);
		if (window.jQuery) {
			// User field doesn't fire native events
			window.jQuery(userField).change(userUpdater);
		}
	});

	function calculatePrice()
	{
		// Set button state
		const saveButton = document.querySelector('.com-dpcalendar-bookingform .dp-button-save');
		if (saveButton) {
			saveButton.disabled = !isSavePossible();
		}

		// Calculate if there are too many tickets selected per event
		let hasEventsSelected = false;
		[].slice.call(document.querySelectorAll('.com-dpcalendar-bookingform .dp-event')).forEach((event) => {
			let selected = 0;
			const events = [].slice.call(event.querySelectorAll('.dp-ticket__amount .dp-select'));
			events.forEach((select) => selected += parseInt(select.options[select.selectedIndex].value));

			events.forEach((select) => {
				if (selected > event.getAttribute('data-ticket-count') && parseInt(select.options[select.selectedIndex].value) > 0) {
					select.classList.add('dp-select_error');
					return;
				}

				select.classList.remove('dp-select_error');
			});

			if (selected) {
				hasEventsSelected = true;
			}
		});

		// Abort when no price calculation is needed
		if (!Joomla.getOptions('DPCalendar.price.url') || !document.querySelector('.dp-price-total__content')) {
			return;
		}

		const taxElement = document.querySelector('.com-dpcalendar-bookingform .dp-tax');

		// Display overbooking error
		if (document.querySelectorAll('.com-dpcalendar-bookingform .dp-ticket__amount .dp-select_error').length > 0) {
			document.querySelector('.com-dpcalendar-bookingform .dp-price-total__content').innerHTML = '';
			Joomla.renderMessages({error: [Joomla.JText._('COM_DPCALENDAR_VIEW_BOOKINGFORM_TICKETS_OVERBOOKED_MESSAGE')]});
			saveButton.disabled = true;

			if (taxElement) {
				taxElement.style.display = 'none';
			}

			return;
		}

		if (!hasEventsSelected && saveButton) {
			saveButton.disabled = true;
		}

		loadDPAssets(['/com_dpcalendar/js/dpcalendar/dpcalendar.js'], () => {
			const form = document.querySelector('.com-dpcalendar-bookingform__form');
			const data = DPCalendar.formToQueryString(form, 'input:not([name="task"]), select');
			DPCalendar.request(
				Joomla.getOptions('DPCalendar.price.url'),
				(json) => {
					const textTax = Joomla.JText._('COM_DPCALENDAR_VIEW_BOOKINGFORM_TAX_' + (json.data.taxinclusive == 1 ? 'IN' : 'EX') + 'CLUSIVE_TEXT');
					const textDiscount = Joomla.JText._('COM_DPCALENDAR_VIEW_BOOKINGFORM_DISCOUNT');

					Object.keys(json.data.events).map((eventId) => {
						const root = form.querySelector('[data-event-id="' + eventId + '"]');
						Object.keys(json.data.events[eventId]).map((type) => {
							Object.keys(json.data.events[eventId][type]).map((id) => {
								const price = json.data.events[eventId][type][id];

								// In markup it is singular
								const selector = type.substring(0, type.length - 1);

								const row = root.querySelector('[data-' + selector + '-price="' + id + '"]');
								if (!row) {
									return;
								}

								const liveCell = row.querySelector('.dp-price__live');
								if (!liveCell) {
									return;
								}

								const info = row.querySelector('.dp-price__info');

								let infoText = '';
								liveCell.innerHTML = price.discount;
								if (price.discount != price.original) {
									row.querySelector('.dp-price__live').classList.remove('dp-price_hidden');
									row.querySelector('.dp-price__original').classList.remove('dp-price_hidden');
									row.querySelector('.dp-price__original').innerHTML = price.original;

									infoText = textDiscount;
								} else {
									row.querySelector('.dp-price__original').classList.add('dp-price_hidden');
									if (info) {
										info.classList.add('dp-price_hidden');
									}
								}

								if (json.data.tax && price.raw != '0.00') {
									infoText += (infoText ? '<br>' : '') + textTax;
								}

								if (info && infoText) {
									info.classList.remove('dp-price_hidden');
									loadDPAssets(['/com_dpcalendar/js/popper/popper.js'], () => {
										loadDPAssets(['/com_dpcalendar/js/tippy/tippy.js', '/com_dpcalendar/css/tippy/tippy.css'], () => {
											tippy(info, {
												interactive: true,
												delay: 100,
												arrow: true,
												content: infoText,
												ignoreAttributes: true,
												popperOptions: {
													modifiers: {
														preventOverflow: {enabled: false},
														hide: {enabled: false}
													}
												}
											});
										});
									});
								}
							});
						});
					});

					document.querySelector('.com-dpcalendar-bookingform .dp-price-total__content').innerHTML = json.data.total;
					document.querySelector('.com-dpcalendar-bookingform .dp-price-total__content').setAttribute('data-raw', json.data.totalraw);

					const taxElement = document.querySelector('.com-dpcalendar-bookingform .dp-tax');
					if (json.data.tax) {
						taxElement.style.display = 'inline-block';
						taxElement.querySelector('.dp-tax__content').innerHTML = json.data.tax;
						taxElement.querySelector('.dp-tax__title').innerHTML = '(' + json.data.taxtitle + ')';
						loadDPAssets(['/com_dpcalendar/js/popper/popper.js'], () => {
							loadDPAssets(['/com_dpcalendar/js/tippy/tippy.js', '/com_dpcalendar/css/tippy/tippy.css'], () => {
								tippy(taxElement.querySelector('.dp-tax__icon'), {
									interactive: true,
									delay: 100,
									arrow: true,
									content: textTax,
									ignoreAttributes: true,
									popperOptions: {
										modifiers: {
											preventOverflow: {enabled: false},
											hide: {enabled: false}
										}
									}
								});
							});
						});
					} else {
						taxElement.style.display = 'none';
					}
				},
				data
			);
		});
	}

	function isSavePossible()
	{
		let accepted = true;
		[].slice.call(document.querySelectorAll('.com-dpcalendar-bookingform__form .dp-input-term')).forEach((term) => {
			if (!term.checked) {
				accepted = false;
			}
		});

		return accepted;
	}

}());
//# sourceMappingURL=default.js.map
