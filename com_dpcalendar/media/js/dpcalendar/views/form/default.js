(function () {
	'use strict';

	/**
	 * @package   DPCalendar
	 * @author    Digital Peak http://www.digital-peak.com
	 * @copyright Copyright (C) 2007 - 2020 Digital Peak. All rights reserved.
	 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
	 */

	function setup()
	{
		const rrule = document.getElementById('jform_rrule');
		if (!rrule) {
			return;
		}

		loadDPAssets(['/com_dpcalendar/js/moment/moment.js'], () => {
			rrule.addEventListener('change', () => {
				updateFormFromRule();
			});

			[].slice.call(document.querySelectorAll('.dp-field-scheduling,#jform_scheduling_monthly_options,#jform_scheduling_daily_weekdays')).forEach((el) => {
				el.addEventListener('click', () => {
					changeVisiblity();
					updateRuleFromForm();
				});
			});
			[].slice.call(document.querySelectorAll('#jform_scheduling_end_date, #jform_scheduling_interval, #jform_scheduling_repeat_count,#jform_scheduling_weekly_days, #jform_scheduling_monthly_days, #jform_scheduling_monthly_week_days, #jform_scheduling_monthly_week')).forEach((el) => {
				el.addEventListener('change', () => {
					updateRuleFromForm();
				});
			});

			updateFormFromRule();
		});
	}

	function updateFormFromRule()
	{
		if (!document.getElementById('jform_scheduling')) {
			changeVisiblity();
			return;
		}

		const rrule = document.getElementById('jform_rrule').value;
		if (!rrule) {
			changeVisiblity();
			return;
		}

		let frequency = null;
		rrule.split(';').forEach((rule) => {
			const parts = rule.split('=');
			if (parts.length === 0) {
				return;
			}

			switch (parts[0]) {
				case 'FREQ':
					[].slice.call(document.getElementById('jform_scheduling').querySelectorAll('input')).forEach((input) => {
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

					parts[1].split(',').forEach((value) => {
						if (frequency == 'MONTHLY') {
							const pos = value.length;
							const day = value.substring(pos - 2, pos);
							let week = value.substring(0, pos - 2);
							if (week == -1) {
								week = 'last';
							}

							if (week) {
								document.getElementById('jform_scheduling_monthly_week').querySelector('option[value="' + week + '"]').selected = true;
							}
							if (day) {
								document.getElementById('jform_scheduling_monthly_week_days').querySelector('option[value="' + day + '"]').selected = true;
							}
						} else {
							document.getElementById('jform_scheduling_weekly_days').querySelector('option[value="' + value + '"]').selected = true;
						}
					});
					break;
				case 'BYMONTHDAY':
					document.getElementById('jform_scheduling_monthly_options').querySelector('input[value="by_day"]').checked = true;
					parts[1].split(',').forEach((value) => {
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
					const untilField = document.getElementById('jform_scheduling_end_date');
					untilField.value = moment.utc(parts[1]).format(untilField.getAttribute('format'));
					untilField.setAttribute('data-date', parts[1].substr(0, 8));
					break;
			}
		});
		changeVisiblity();
	}

	function updateRuleFromForm()
	{
		if (!document.getElementById('jform_scheduling')) {
			return;
		}

		let rule = '';
		if (document.getElementById('jform_scheduling1').checked) {
			rule = 'FREQ=DAILY';
			if (document.getElementById('jform_scheduling_daily_weekdays0').checked) {
				rule = 'FREQ=WEEKLY;BYDAY=MO,TU,WE,TH,FR';
			}
		}
		if (document.getElementById('jform_scheduling2').checked) {
			rule = 'FREQ=WEEKLY';

			const boxes = [].slice.call(document.querySelectorAll('#jform_scheduling_weekly_days option:checked'));
			if (boxes.length > 0) {
				rule += ';BYDAY=';
				boxes.forEach((option) => {
					rule += option.value + ',';
				});
				rule = rule.slice(0, -1);
			}
		}
		if (document.getElementById('jform_scheduling3').checked) {
			rule = 'FREQ=MONTHLY';
			if (document.getElementById('jform_scheduling_monthly_options0').checked) {
				const boxes = [].slice.call(document.querySelectorAll('#jform_scheduling_monthly_days option:checked'));
				if (boxes.length > 0) {
					rule += ';BYMONTHDAY=';
					boxes.forEach((option) => {
						rule += option.value + ',';
					});
					rule = rule.slice(0, -1);
				}
			} else {
				const boxes = [].slice.call(document.querySelectorAll('#jform_scheduling_monthly_week option:checked'));
				if (boxes.length > 0) {
					rule += ';BYDAY=';
					boxes.forEach((option) => {
						const days = [].slice.call(document.querySelectorAll('#jform_scheduling_monthly_week_days option:checked'));
						if (days.length == 0) {
							return;
						}

						let week = option.value;
						if (week == 'last') {
							week = -1;
						}

						days.forEach((d) => {
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
			const interval = document.getElementById('jform_scheduling_interval').value;
			if (interval > 0) {
				rule += ';INTERVAL=' + interval;
			}
			const count = document.getElementById('jform_scheduling_repeat_count').value;
			if (count > 0) {
				rule += ';COUNT=' + count;
			}

			const untilField = document.getElementById('jform_scheduling_end_date');
			const until = moment(untilField.value, untilField.getAttribute('data-format'));
			if (until.isValid()) {
				rule += ';UNTIL=' + until.format('YYYYMMDD') + 'T235900Z';
			}
		}
		document.getElementById('jform_rrule').value = rule;
	}

	function changeVisiblity()
	{
		// Disable all fields initially
		document.querySelector('.dp-field-scheduling-end-date').classList.add('dp-control_hidden');
		document.querySelector('.dp-field-scheduling-interval').classList.add('dp-control_hidden');
		document.querySelector('.dp-field-scheduling-repeat-count').classList.add('dp-control_hidden');
		document.querySelector('.dp-field-rrule').classList.add('dp-control_hidden');
		document.querySelector('.dp-field-scheduling-daily-weekdays').classList.add('dp-control_hidden');
		document.querySelector('.dp-field-scheduling-weekly-days').classList.add('dp-control_hidden');
		document.querySelector('.dp-field-scheduling-monthly-options').classList.add('dp-control_hidden');
		document.querySelector('.dp-field-scheduling-monthly-week').classList.add('dp-control_hidden');
		document.querySelector('.dp-field-scheduling-monthly-week-days').classList.add('dp-control_hidden');
		document.querySelector('.dp-field-scheduling-monthly-days').classList.add('dp-control_hidden');

		// Scheduling field is not rendered
		if (!document.getElementById('jform_scheduling')) {
			return;
		}

		// Scheduling is not activated
		if (document.getElementById('jform_scheduling0').checked) {
			return;
		}

		// Display default fields
		document.querySelector('.dp-field-scheduling-end-date').classList.remove('dp-control_hidden');
		document.querySelector('.dp-field-scheduling-interval').classList.remove('dp-control_hidden');
		document.querySelector('.dp-field-scheduling-repeat-count').classList.remove('dp-control_hidden');
		document.querySelector('.dp-field-rrule').classList.remove('dp-control_hidden');

		// Daily
		if (document.getElementById('jform_scheduling1').checked) {
			document.querySelector('.dp-field-scheduling-daily-weekdays').classList.remove('dp-control_hidden');
		}

		// Weekly
		if (document.getElementById('jform_scheduling2').checked) {
			document.querySelector('.dp-field-scheduling-weekly-days').classList.remove('dp-control_hidden');
		}

		// Monthly
		if (document.getElementById('jform_scheduling3').checked) {
			document.querySelector('.dp-field-scheduling-monthly-options').classList.remove('dp-control_hidden');

			if (document.getElementById('jform_scheduling_monthly_options0').checked) {
				document.querySelector('.dp-field-scheduling-monthly-days').classList.remove('dp-control_hidden');
			} else {
				document.querySelector('.dp-field-scheduling-monthly-week').classList.remove('dp-control_hidden');
				document.querySelector('.dp-field-scheduling-monthly-week-days').classList.remove('dp-control_hidden');
			}
		}
	}

	/**
	 * @package   DPCalendar
	 * @author    Digital Peak http://www.digital-peak.com
	 * @copyright Copyright (C) 2007 - 2020 Digital Peak. All rights reserved.
	 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
	 */

	function setup$1()
	{
		loadDPAssets(['/com_dpcalendar/js/dpcalendar/layouts/block/datepicker.js', '/com_dpcalendar/js/dpcalendar/layouts/block/timepicker.js']);

		[].slice.call(document.querySelectorAll('#jform_all_day input')).forEach((input) => {
			input.addEventListener('click', (e) => {
				if (input.value == 0) {
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

		const check = DPCalendar.debounce(checkOverlapping, 2000);
		[].slice.call(document.querySelectorAll('#jform_start_date, #jform_start_date_time, #jform_end_date, #jform_end_date_time, #jform_rooms')).forEach((input) => {
			// When the start date changes we need to wait till the end date get adjusted as well
			input.addEventListener('change', check);
		});

		document.getElementById('jform_catid').addEventListener('change', (e) => {
			// Because of com_fields, we need to call it with a delay
			check();
		});

		check();
	}

	function checkOverlapping()
	{
		const box = document.querySelector('.com-dpcalendar-eventform__overlapping');
		if (!box) {
			return;
		}

		box.style.display = 'none';

		loadDPAssets(['/com_dpcalendar/js/domurl/url.js'], () => {
			// Chosen doesn't update the selected value
			const url = new Url();
			DPCalendar.request(
				'task=event.overlapping',
				(json) => {
					if (!json.data.message) {
						return;
					}

					box.style.display = 'block';
					box.className = 'com-dpcalendar-eventform__overlapping dp-info-box' + (json.data.count ? '' : ' dp-info-box_success');
					box.innerHTML = json.data.message;

					if (!box.getAttribute('data-overlapping')) {
						return;
					}

					document.querySelector('.dp-button-apply').disabled = json.data.count > 0;
					document.querySelector('.dp-button-save').disabled = json.data.count > 0;
					document.querySelector('.dp-button-save2new').disabled = json.data.count > 0;
					document.querySelector('.dp-button-save2copy').disabled = json.data.count > 0;
				},
				DPCalendar.formToQueryString(document.querySelector('.com-dpcalendar-eventform__form'), 'input:not([name=task]), select') + (url.query.e_id ? '&id=' + url.query.e_id : '')
			);
		});
	}

	/**
	 * @package   DPCalendar
	 * @author    Digital Peak http://www.digital-peak.com
	 * @copyright Copyright (C) 2007 - 2020 Digital Peak. All rights reserved.
	 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
	 */

	function setup$2()
	{
		const map = document.querySelector('.com-dpcalendar-eventform .dp-map');
		if (map != null) {
			loadDPAssets(['/com_dpcalendar/js/dpcalendar/map.js'], () => {
				map.addEventListener('dp-map-loaded', () => {
					updateLocationFrame();
				});
				if (map.dpmap) {
					updateLocationFrame();
				}
			});
		}

		const rooms = document.querySelector('.com-dpcalendar-eventform .dp-field-rooms');
		if (rooms && rooms.querySelectorAll('option').length == 0) {
			rooms.style.display = 'none';
		}

		const geoComplete = document.querySelector('.com-dpcalendar-eventform #jform_location_lookup');
		if (!geoComplete) {
			return;
		}

		loadDPAssets(['/com_dpcalendar/js/dpcalendar/layouts/block/autocomplete.js'], () => {
			DPCalendar.autocomplete.create(geoComplete);
		});

		geoComplete.addEventListener('dp-autocomplete-select', (e) => {
			DPCalendar.request(
				'task=event.newlocation',
				(json) => {
					if (json.data.id == null || json.data.display == null) {
						return;
					}

					// Reset the input field
					geoComplete.value = '';

					const select = document.getElementById('jform_location_ids');
					if (select._choicejs.getValue(true).indexOf(json.data.id) != -1) {
						return;
					}

					select._choicejs.setChoices(
						[{
							value: json.data.id ? json.data.id : json.data.display,
							label: json.data.display,
							selected: true
						}],
						'value',
						'label'
					);

					updateLocationFrame();
				},
				'lookup=' + e.detail.value + '&lookup_title=' + e.detail.title
			);
		});

		geoComplete.addEventListener('dp-autocomplete-change', (e) => {
			let task = 'location.searchloc';
			if (window.location.href.indexOf('administrator') == -1) {
				task = 'locationform.searchloc';
			}
			DPCalendar.request(
				'task=' + task + '&loc=' + encodeURIComponent(e.target.value.trim()),
				(json) => DPCalendar.autocomplete.setItems(geoComplete, json.data)
			);
		});
	}

	function updateLocationFrame()
	{
		const map = document.querySelector('.com-dpcalendar-eventform .dp-map');
		if (map == null || map.dpmap == null) {
			return;
		}

		if (map.dpmap.invalidateSize) {
			map.dpmap.invalidateSize();
		}

		DPCalendar.Map.clearMarkers(map);
		[].slice.call(document.querySelectorAll('#jform_location_ids option:checked')).forEach((option) => {
			const content = option.innerHTML;
			const parts = content.substring(content.lastIndexOf('[') + 1, content.lastIndexOf(']')).split(':');
			if (parts.length < 2 || (parts[0] == 0 && parts[1] == 0)) {
				return;
			}

			DPCalendar.Map.createMarker(map, {latitude: parts[0], longitude: parts[1], title: content});
		});
	}

	/**
	 * @package   DPCalendar
	 * @author    Digital Peak http://www.digital-peak.com
	 * @copyright Copyright (C) 2007 - 2020 Digital Peak. All rights reserved.
	 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
	 */

	function setup$3()
	{
		if (parseInt(document.getElementById('jform_id').value) != 0) {
			return
		}

		loadDPAssets(['/com_dpcalendar/js/domurl/url.js', '/com_dpcalendar/js/dpcalendar/layouts/block/autocomplete.js'], () => {
			const titleInput = document.getElementById('jform_title');
			DPCalendar.autocomplete.create(titleInput);

			titleInput.addEventListener('dp-autocomplete-select', (e) => {
				document.querySelector('input[name=template_event_id]').value = e.detail.value;
				Joomla.submitbutton('event.reloadfromevent');
			});

			const url = new Url();
			titleInput.addEventListener('dp-autocomplete-change', (e) => {
				loadDPAssets(['/com_dpcalendar/js/dpcalendar/dpcalendar.js'], () => {
					DPCalendar.request(
						'task=event.similar',
						(json) => {
							DPCalendar.autocomplete.setItems(titleInput, json.data);
						},
						DPCalendar.formToQueryString(document.querySelector('.com-dpcalendar-eventform__form'), 'input:not([name=task]), select') + '&id=' + url.query.e_id
					);
				});
			});
		});
	}

	/**
	 * @package   DPCalendar
	 * @author    Digital Peak http://www.digital-peak.com
	 * @copyright Copyright (C) 2007 - 2020 Digital Peak. All rights reserved.
	 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
	 */

	function setup$4()
	{
		const captcha = document.querySelector('.dp-field-captcha');
		if (!captcha) {
			return;
		}

		document.querySelector('.com-dpcalendar-eventform__form').appendChild(captcha);
	}

	/**
	 * @package   DPCalendar
	 * @author    Digital Peak http://www.digital-peak.com
	 * @copyright Copyright (C) 2007 - 2020 Digital Peak. All rights reserved.
	 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
	 */

	function setup$5()
	{
		document.getElementById('jform_catid').addEventListener('change', (e) => {
			// If there is an external calendar, reload the form
			for (let i = 0; i < e.target.length; i++) {
				if (e.target.value && isNaN(parseInt(e.target.options[i].value))) {
					const loader = document.querySelector('.dp-loader');
					if (loader) {
						loader.classList.remove('dp-loader_hidden');
					}

					Joomla.submitbutton('event.reload');
					return true;
				}
			}
		});

		const locationIds = document.getElementById('jform_location_ids');
		if (locationIds) {
			locationIds.addEventListener('change', (e) => {
				if (!document.getElementById('jform_rooms')) {
					return true;
				}

				// Only reload when it is a existing location as it can have rooms
				if (isNaN(e.detail.value)) {
					return;
				}

				const loader = document.querySelector('.dp-loader');
				if (loader) {
					loader.classList.remove('dp-loader_hidden');
				}

				Joomla.submitbutton('event.reload');
			});
		}

		[].slice.call(document.querySelectorAll('.com-dpcalendar-eventform__actions .dp-button')).forEach((button) => {
			button.addEventListener('click', () => {
				Joomla.submitbutton('event.' + button.getAttribute('data-task'));
			});
		});

		Joomla.submitbutton = (task) => {
			const form = document.getElementsByName('adminForm')[0];
			if (form && (task.indexOf('reload') > -1 || task.indexOf('cancel') > -1 || task.indexOf('delete') > -1 || document.formvalidator.isValid(form))) {

				const text = Joomla.JText._('COM_DPCALENDAR_VIEW_EVENT_SEND_TICKET_HOLDERS_NOFICATION');
				if (text && (task.indexOf('save') > -1 || task.indexOf('apply') > -1) && confirm(text)) {
					document.getElementById('jform_notify_changes').value = 1;
				}

				const loader = document.querySelector('.dp-loader');
				if (loader) {
					loader.classList.remove('dp-loader_hidden');
				}

				Joomla.submitform(task, form);
			}
		};
	}

	/**
	 * @package   DPCalendar
	 * @author    Digital Peak http://www.digital-peak.com
	 * @copyright Copyright (C) 2007 - 2020 Digital Peak. All rights reserved.
	 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
	 */

	document.addEventListener('DOMContentLoaded', () => {
		loadDPAssets(['/com_dpcalendar/js/dpcalendar/dpcalendar.js'], () => {
			setup();
			setup$1();
			setup$2();
			setup$3();
			setup$4();
			setup$5();
		});

		if (Joomla.JText._('COM_DPCALENDAR_ONLY_AVAILABLE_SUBSCRIBERS')) {
			[].slice.call(document.querySelectorAll('.dp-field-scheduling .controls, .dp-tabs__tab-booking .controls')).forEach((el) => {
				const option = document.createElement('span');
				option.className = 'dp-free-information-text';
				option.innerText = Joomla.JText._('COM_DPCALENDAR_ONLY_AVAILABLE_SUBSCRIBERS');
				el.appendChild(option);
			});
		}

		loadDPAssets(['/com_dpcalendar/js/choices/choices.js', '/com_dpcalendar/css/choices/choices.css'], () => {
			[].slice.call(document.querySelectorAll('.com-dpcalendar-eventform select:not(#jform_color):not(#jform_tags):not(.dp-timezone__select)')).forEach((select) => {
				select._choicejs = new Choices(
					select,
					{
						itemSelectText: '',
						noChoicesText: '',
						shouldSortItems: false,
						shouldSort: false,
						removeItemButton: select.id != 'jform_catid',
						searchResultLimit: 30
					}
				);
			});
		});
	});

}());
//# sourceMappingURL=default.js.map
