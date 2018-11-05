DPCalendar = window.DPCalendar || {};

(function (document, Joomla, DPCalendar) {
	'use strict';

	document.addEventListener('DOMContentLoaded', function () {
		var rrule = document.getElementById('jform_rrule');
		if (rrule) {
			rrule.addEventListener('change', function (e) {
				updateFormFromRule();
			});
			[].slice.call(document.querySelectorAll('.dp-field-scheduling,#jform_scheduling_monthly_options,#jform_scheduling_daily_weekdays')).forEach(function (el) {
				el.addEventListener('click', function (e) {
					changeVisiblity();
					updateRuleFromForm();
				});
			});
			[].slice.call(document.querySelectorAll('#jform_scheduling_end_date, #jform_scheduling_interval, #jform_scheduling_repeat_count,#jform_scheduling_weekly_days, #jform_scheduling_monthly_days, #jform_scheduling_monthly_week_days, #jform_scheduling_monthly_week')).forEach(function (el) {
				el.addEventListener('change', function (e) {
					updateRuleFromForm();
				});
			});

			updateFormFromRule();
		}

		[].slice.call(document.querySelectorAll('#jform_all_day input')).forEach(function (input) {
			input.addEventListener('click', function (e) {
				if (this.value == 0) {
					document.getElementById('jform_start_date_time').style.display = 'inline-block';
					document.getElementById('jform_end_date_time').style.display = 'inline-block';
				} else {
					document.getElementById('jform_start_date_time').style.display = 'none';
					document.getElementById('jform_end_date_time').style.display = 'none';
				}

				checkOverlapping();
			});
		});

		if (document.getElementById('jform_all_day0').checked
			|| (!document.getElementById('jform_all_day0').checked && !document.getElementById('jform_all_day1').checked)
		) {
			document.getElementById('jform_start_date_time').style.display = 'inline-block';
			document.getElementById('jform_end_date_time').style.display = 'inline-block';
		} else {
			document.getElementById('jform_start_date_time').style.display = 'none';
			document.getElementById('jform_end_date_time').style.display = 'none';
		}

		[].slice.call(document.querySelectorAll('#jform_start_date, #jform_start_date_time, #jform_end_date, #jform_end_date_time, #jform_rooms')).forEach(function (input) {
			input.addEventListener('change', (function (e) {
				setTimeout(checkOverlapping, 2000);
			}));
		});

		document.getElementById('jform_catid').addEventListener('change', function (e) {
			// If there is an external calendar, reload the form
			for (var i = 0; i < e.target.length; i++) {
				if (e.target.value && isNaN(parseInt(e.target.options[i].value))) {
					Joomla.loadingLayer('show');
					Joomla.submitbutton('event.reload');
					return true;
				}
			}

			// Because of com_fields, we need to call it with a delay
			setTimeout(checkOverlapping, 2000);
		});

		setTimeout(checkOverlapping, 2000);
		setTimeout(updateLocationFrame, 2000);

		if (Joomla.JText._('COM_DPCALENDAR_ONLY_AVAILABLE_SUBSCRIBERS')) {
			[].slice.call(document.querySelectorAll('.dp-field-scheduling .controls, .dp-tabs__tab-booking .controls')).forEach(function (el) {
				var option = document.createElement('span');
				option.className = 'dp-free-information-text';
				option.innerText = Joomla.JText._('COM_DPCALENDAR_ONLY_AVAILABLE_SUBSCRIBERS');
				el.appendChild(option);
			});
		}

		[].slice.call(document.querySelectorAll('.com-dpcalendar-eventform__actions .dp-button')).forEach(function (button) {
			button.addEventListener('click', function () {
				Joomla.submitbutton('event.' + this.getAttribute('data-task'));
			})
		});

		Joomla.submitbutton = function (task) {
			var form = document.getElementsByName('adminForm')[0];
			if (form && (task.indexOf('reload') > -1 || task.indexOf('cancel') > -1 || task.indexOf('delete') > -1 || document.formvalidator.isValid(form))) {
				Joomla.submitform(task, form);
			}
		};

		[].slice.call(document.querySelectorAll('.com-dpcalendar-eventform select')).forEach(function (select) {
			new SlimSelect({select: select});
		});

		if (parseInt(document.getElementById('jform_id').value) == 0) {
			// Similar find
			var titleInput = document.getElementById('jform_title');

			DPCalendar.autocomplete.create(titleInput);

			titleInput.addEventListener('dp-autocomplete-select', function (e) {
				document.querySelector('input[name=template_event_id]').value = e.detail.value;
				Joomla.submitbutton('event.reloadfromevent');
			});

			var url = new Url();
			titleInput.addEventListener('dp-autocomplete-change', function (e) {
				DPCalendar.request(
					'task=event.similar',
					function (json) {
						DPCalendar.autocomplete.setItems(titleInput, json.data);
					},
					DPCalendar.formToQueryString(document.querySelector('.com-dpcalendar-eventform__form'), 'input:not([name=task]), select') + '&id=' + url.query.e_id
				);
			});
		}

		// If the location stuff is not loaded, return here
		if (!document.querySelector('.com-dpcalendar-eventform__location')) {
			return;
		}

		document.getElementById('jform_location_ids').addEventListener('change', function (e) {
			Joomla.loadingLayer('show');
			Joomla.submitbutton('event.reload');
		});

		document.querySelector('.com-dpcalendar-eventform__location-form .dp-button-cancel').addEventListener('click', function () {
			var event = document.createEvent('CustomEvent');
			event.initCustomEvent('click');
			document.querySelector('.com-dpcalendar-eventform__toggle').dispatchEvent(event);
		});

		document.querySelector('.com-dpcalendar-eventform__location-form .dp-button-save').addEventListener('click', function () {
			var fill = function (data, name) {
				var input = document.getElementById('location_' + name);
				if (!input) {
					return;
				}
				data['jform'][name] = input.value;
			};
			var data = [];
			data['jform'] = [];
			fill(data, 'title');
			fill(data, 'country');
			fill(data, 'province');
			fill(data, 'city');
			fill(data, 'zip');
			fill(data, 'street');
			fill(data, 'number');
			data['jform']['state'] = 1;
			data['jform']['language'] = '*';
			data[document.querySelector('[name="location_token"]').value] = '1';
			data['ajax'] = '1';
			data['id'] = 0;

			DPCalendar.request(
				'task=locationform.save',
				function (json) {
					if (json.data.id != null && json.data.display != null) {
						var select = document.getElementById('jform_location_ids');

						// Add the option
						var option = document.createElement('option');
						option.value = json.data.id;
						option.selected = true;
						option.innerText = json.data.display;
						select.appendChild(option);

						updateLocationFrame();

						// Reset the input boxes
						[].slice.call(document.querySelectorAll('.com-dpcalendar-eventform__location-form input')).forEach(function (el) {
							el.value = ''
						});
					}
				},
				DPCalendar.arrayToQueryString(data)
			);
		});

		// Toggle the location form
		var toggle = document.querySelector('.com-dpcalendar-eventform__toggle');
		if (toggle) {
			toggle.addEventListener('click', function () {
				DPCalendar.slideToggle(document.querySelector('.com-dpcalendar-eventform__location-form'), function (fadeIn) {
					var root = document.querySelector('.com-dpcalendar-eventform__toggle');

					if (!fadeIn) {
						root.querySelector('[data-direction="up"]').style.display = 'none';
						root.querySelector('[data-direction="down"]').style.display = 'inline';
					} else {
						root.querySelector('[data-direction="up"]').style.display = 'inline';
						root.querySelector('[data-direction="down"]').style.display = 'none';
					}
				});
			});
		}

		// Don't validate the fields of the location form together with the global form
		[].slice.call(document.querySelectorAll('.com-dpcalendar-eventform__location-form input')).forEach(function (input) {
			input.classList.add('novalidate');
		});
	});

	function checkOverlapping() {
		var box = document.querySelector('.com-dpcalendar-eventform__overlapping');
		if (!box) {
			return;
		}
		box.style.display = 'none';

		// Chosen doesn't update the selected value
		var url = new Url();
		DPCalendar.request(
			'task=event.overlapping',
			function (json) {
				if (json.data.message) {
					box.style.display = 'block';
					box.className = 'com-dpcalendar-eventform__overlapping dp-info-box' + (json.data.count ? '' : ' dp-info-box_success');
					box.innerHTML = json.data.message;

					if (box.getAttribute('data-overlapping')) {
						document.querySelector('.dp-button-apply').disabled = json.data.count > 0;
						document.querySelector('.dp-button-save').disabled = json.data.count > 0;
						document.querySelector('.dp-button-save2new').disabled = json.data.count > 0;
						document.querySelector('.dp-button-save2copy').disabled = json.data.count > 0;
					}
				}
			},
			DPCalendar.formToQueryString(document.querySelector('.com-dpcalendar-eventform__form'), 'input:not([name=task]), select') + '&id=' + url.query.e_id
		);
	}

	function updateFormFromRule() {
		var rrule = document.getElementById('jform_rrule').value;
		if (!rrule) {
			changeVisiblity();
			return;
		}

		var frequency = null;
		rrule.split(';').forEach(function (rule) {
			var parts = rule.split('=');
			if (parts.length === 0) {
				return;
			}

			switch (parts[0]) {
				case 'FREQ':
					[].slice.call(document.getElementById('jform_scheduling').querySelectorAll('input')).forEach(function (input) {
						input.checked = input.value == parts[1];
						if (input.value == parts[1]) {
							frequency = input.value;
						}

						if (parts[1] == 'DAILY') {
							document.getElementById('jform_scheduling_daily_weekdays0').checked = false;
							document.getElementById('jform_scheduling_daily_weekdays1').checked = true;
						}
					});
					break;
				case 'BYDAY':
					// Daily without weekend
					if (frequency == 'WEEKLY' && parts[1] == 'MO,TU,WE,TH,FR') {
						document.getElementById('jform_scheduling_daily_weekdays0').checked = true;
						document.getElementById('jform_scheduling_daily_weekdays1').checked = false;
						document.getElementById('jform_scheduling1').checked = true;
					}

					parts[1].split(',').forEach(function (value) {
						if (frequency == 'MONTHLY') {
							var pos = value.length;
							var day = value.substring(pos - 2, pos);
							var week = value.substring(0, pos - 2);

							if (week == -1) {
								week = 'last';
							}

							document.getElementById('jform_scheduling_monthly_week').querySelector('option[value="' + week + '"]').selected = true;
							document.getElementById('jform_scheduling_monthly_week_days').querySelector('option[value="' + day + '"]').selected = true;
						} else {
							document.getElementById('jform_scheduling_weekly_days').querySelector('option[value="' + value + '"]').selected = true;
						}
					});
					break;
				case 'BYMONTHDAY':
					document.getElementById('jform_scheduling_monthly_options').querySelector('input[value="by_day"]').checked = true;
					parts[1].split(',').forEach(function (value) {
						document.getElementById('jform_scheduling_monthly_days').querySelector('option[value="' + value + '"]').selected = true;
					});
					break;
				case 'COUNT':
					document.getElementById('jform_scheduling_repeat_count').value = parts[1];
					break;
				case 'INTERVAL':
					document.getElementById('jform_scheduling_interval').value = parts[1];
					break;
				case 'UNTIL':
					var untilField = document.getElementById('jform_scheduling_end_date');
					untilField.value = moment.utc(parts[1]).format(untilField.getAttribute('format'));
					untilField.setAttribute('data-date', parts[1].substr(0, 8));
					break;
			}
		});
		changeVisiblity();
	}

	function updateRuleFromForm() {
		var rule = '';
		if (document.getElementById('jform_scheduling1').checked) {
			rule = 'FREQ=DAILY';
			if (document.getElementById('jform_scheduling_daily_weekdays0').checked) {
				rule = 'FREQ=WEEKLY;BYDAY=MO,TU,WE,TH,FR';
			}
		}
		if (document.getElementById('jform_scheduling2').checked) {
			rule = 'FREQ=WEEKLY';

			var boxes = [].slice.call(document.querySelectorAll('#jform_scheduling_weekly_days option:checked'));
			if (boxes.length > 0) {
				rule += ';BYDAY=';
				boxes.forEach(function (option) {
					rule += option.value + ',';
				});
				rule = rule.slice(0, -1);
			}
		}
		if (document.getElementById('jform_scheduling3').checked) {
			rule = 'FREQ=MONTHLY';
			if (document.getElementById('jform_scheduling_monthly_options0').checked) {
				var boxes = [].slice.call(document.querySelectorAll('#jform_scheduling_monthly_days option:checked'));
				if (boxes.length > 0) {
					rule += ';BYMONTHDAY=';
					boxes.forEach(function (option) {
						rule += option.value + ',';
					});
					rule = rule.slice(0, -1);
				}
			} else {
				var boxes = [].slice.call(document.querySelectorAll('#jform_scheduling_monthly_week option:checked'));
				if (boxes.length > 0) {
					rule += ';BYDAY=';
					boxes.forEach(function (option) {
						var days = [].slice.call(document.querySelectorAll('#jform_scheduling_monthly_week_days option:checked'));
						if (days.length == 0) {
							return;
						}

						var week = option.value;
						if (week == 'last') {
							week = -1;
						}

						days.forEach(function (d) {
							rule += week + d.value + ',';
						});
					});
					if (rule.endsWith(',')) {
						rule = rule.slice(0, -1);
					}
				}
			}
		}
		if (document.getElementById('jform_scheduling4').checked) {
			rule = 'FREQ=YEARLY';
		}
		if (rule.length > 1) {
			var interval = document.getElementById('jform_scheduling_interval').value;
			if (interval > 0) {
				rule += ';INTERVAL=' + interval;
			}
			var count = document.getElementById('jform_scheduling_repeat_count').value;
			if (count > 0) {
				rule += ';COUNT=' + count;
			}

			var untilField = document.getElementById('jform_scheduling_end_date');
			var until = moment(untilField.value, untilField.getAttribute('data-format'));
			if (until.isValid()) {
				rule += ';UNTIL=' + until.format('YYYYMMDD') + 'T235900Z';
			}
		}
		document.getElementById('jform_rrule').value = rule;
	}

	function updateLocationFrame() {
		var map = document.querySelector('.com-dpcalendar-eventform__location .dp-map');
		if (map == null || map.dpmap == null) {
			return;
		}

		if (map.dpmap.invalidateSize) {
			map.dpmap.invalidateSize();
		}

		DPCalendar.Map.clearMarkers(map.dpmap);
		[].slice.call(document.querySelectorAll('#jform_location_ids option:checked')).forEach(function (option) {
			var content = option.innerHTML;
			var parts = content.substring(content.lastIndexOf('[') + 1, content.lastIndexOf(']')).split(':');
			if (parts.length < 2) {
				return;
			}
			if (parts[0] == 0 && parts[1] == 0) {
				return;
			}
			DPCalendar.Map.createMarker(map.dpmap, {latitude: parts[0], longitude: parts[1], title: content});
		});
	}

	function changeVisiblity() {
		// no scheduling
		if (document.getElementById('jform_scheduling0').checked) {
			document.querySelector('.dp-field-scheduling-end-date').style.display = 'none';
			document.querySelector('.dp-field-scheduling-interval').style.display = 'none';
			document.querySelector('.dp-field-scheduling-repeat-count').style.display = 'none';
			document.querySelector('.dp-field-rrule').style.display = 'none';
		} else {
			document.querySelector('.dp-field-scheduling-end-date').style.display = 'block'
			document.querySelector('.dp-field-scheduling-interval').style.display = 'block'
			document.querySelector('.dp-field-scheduling-repeat-count').style.display = 'block'
			document.querySelector('.dp-field-rrule').style.display = 'block'
		}

		// daily
		if (document.getElementById('jform_scheduling1').checked) {
			document.querySelector('.dp-field-scheduling-daily-weekdays').style.display = 'block'
		} else {
			document.querySelector('.dp-field-scheduling-daily-weekdays').style.display = 'none';
		}

		// weekly
		if (document.getElementById('jform_scheduling2').checked) {
			document.querySelector('.dp-field-scheduling-weekly-days').style.display = 'block'
		} else {
			document.querySelector('.dp-field-scheduling-weekly-days').style.display = 'none';
		}

		// monthly
		if (document.getElementById('jform_scheduling3').checked) {
			document.querySelector('.dp-field-scheduling-monthly-options').style.display = 'block'
			document.querySelector('.dp-field-scheduling-monthly-week').style.display = 'block'
			document.querySelector('.dp-field-scheduling-monthly-week-days').style.display = 'block'
			document.querySelector('.dp-field-scheduling-monthly-days').style.display = 'block'

			if (document.getElementById('jform_scheduling_monthly_options0').checked) {
				document.querySelector('.dp-field-scheduling-monthly-week').style.display = 'none';
				document.querySelector('.dp-field-scheduling-monthly-week-days').style.display = 'none';
				document.querySelector('.dp-field-scheduling-monthly-days').style.display = 'block'
			} else {
				document.querySelector('.dp-field-scheduling-monthly-week').style.display = 'block'
				document.querySelector('.dp-field-scheduling-monthly-week-days').style.display = 'block'
				document.querySelector('.dp-field-scheduling-monthly-days').style.display = 'none';
			}
		} else {
			document.querySelector('.dp-field-scheduling-monthly-options').style.display = 'none';
			document.querySelector('.dp-field-scheduling-monthly-week').style.display = 'none';
			document.querySelector('.dp-field-scheduling-monthly-week-days').style.display = 'none';
			document.querySelector('.dp-field-scheduling-monthly-days').style.display = 'none';
		}
	}
}(document, Joomla, DPCalendar));
