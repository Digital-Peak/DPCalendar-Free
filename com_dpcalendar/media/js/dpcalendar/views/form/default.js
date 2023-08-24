/**
 * @package   DPCalendar
 * @copyright Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */
(function () {
	'use strict';
	function setup$5() {
		const rrule = document.getElementById('jform_rrule');
		if (!rrule) {
			return;
		}
		loadDPAssets(['/com_dpcalendar/js/dayjs/dayjs.js'], () => {
			rrule.addEventListener('change', updateFormFromRule);
			document.getElementById('jform_scheduling_end_date').addEventListener('change', (e) => {
				if (e.target.classList.contains('dp-datepicker__input') && !e.target.dpPikaday) {
					return;
				}
				updateRuleFromForm();
			});
			updateFormFromRule();
			[].slice.call(document.querySelectorAll('.dp-field-scheduling, #jform_scheduling_monthly_options, #jform_scheduling_daily_weekdays')).forEach((el) => {
				el.addEventListener('click', () => {
					changeVisiblity();
					updateRuleFromForm();
				});
			});
			[].slice.call(document.querySelectorAll('#jform_scheduling_interval, #jform_scheduling_repeat_count,#jform_scheduling_weekly_days, #jform_scheduling_monthly_days, #jform_scheduling_monthly_week_days, #jform_scheduling_monthly_week')).forEach((el) => {
				el.addEventListener('change', updateRuleFromForm);
			});
		});
	}
	function updateFormFromRule() {
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
							document.querySelector('#jform_scheduling_monthly_week option[value="' + week + '"]').selected = true;
							document.getElementById('jform_scheduling_monthly_week').dispatchEvent(new Event('change'));
						}
						if (day) {
							document.querySelector('#jform_scheduling_monthly_week_days option[value="' + day + '"]').selected = true;
							document.getElementById('jform_scheduling_monthly_week_days').dispatchEvent(new Event('change'));
						}
					} else {
						document.querySelector('#jform_scheduling_weekly_days option[value="' + value + '"]').selected = true;
						document.getElementById('jform_scheduling_weekly_days').dispatchEvent(new Event('change'));
					}
				});
				break;
			case 'BYMONTHDAY':
				document.getElementById('jform_scheduling_monthly_options').querySelector('input[value="by_day"]').checked = true;
				parts[1].split(',').forEach((value) =>
					document.querySelector('#jform_scheduling_monthly_days option[value="' + value + '"]').selected = true
				);
				document.getElementById('jform_scheduling_monthly_days').dispatchEvent(new Event('change'));
				break;
			case 'COUNT':
				document.getElementById('jform_scheduling_repeat_count').value = parts[1];
				break;
			case 'INTERVAL':
				document.getElementById('jform_scheduling_interval').value = parts[1];
				break;
			case 'UNTIL': {
				const untilField = document.getElementById('jform_scheduling_end_date');
				const date = dayjs.utc(parts[1].replace('Z', ''), 'YYYYMMDD\Thhmmss');
				untilField.value = date.format(untilField.getAttribute('data-format'));
				untilField.setAttribute('data-date', date.format('YYYY-MM-DD'));
				break;
			}
			}
		});
		changeVisiblity();
	}
	function updateRuleFromForm() {
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
				boxes.forEach((option) => rule += option.value + ',');
				rule = rule.slice(0, -1);
			}
		}
		if (document.getElementById('jform_scheduling3').checked) {
			rule = 'FREQ=MONTHLY';
			if (document.getElementById('jform_scheduling_monthly_options0').checked) {
				const boxes = [].slice.call(document.querySelectorAll('#jform_scheduling_monthly_days option:checked'));
				if (boxes.length > 0) {
					rule += ';BYMONTHDAY=';
					boxes.forEach((option) => rule += option.value + ',');
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
						days.forEach((d) => rule += week + d.value + ',');
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
			const until = dayjs(untilField.value, untilField.getAttribute('data-format'));
			if (until.isValid()) {
				rule += ';UNTIL=' + until.format('YYYYMMDD') + 'T235900Z';
			}
		}
		const oldValue = document.getElementById('jform_rrule').value;
		document.getElementById('jform_rrule').value = rule;
		if (oldValue != rule) {
			document.getElementById('jform_rrule').dispatchEvent(new Event('change'));
		}
	}
	function changeVisiblity() {
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
		if (!document.getElementById('jform_scheduling')) {
			return;
		}
		if (document.getElementById('jform_scheduling0').checked) {
			return;
		}
		document.querySelector('.dp-field-scheduling-end-date').classList.remove('dp-control_hidden');
		document.querySelector('.dp-field-scheduling-interval').classList.remove('dp-control_hidden');
		document.querySelector('.dp-field-scheduling-repeat-count').classList.remove('dp-control_hidden');
		document.querySelector('.dp-field-rrule').classList.remove('dp-control_hidden');
		if (document.getElementById('jform_scheduling1').checked) {
			document.querySelector('.dp-field-scheduling-daily-weekdays').classList.remove('dp-control_hidden');
		}
		if (document.getElementById('jform_scheduling2').checked) {
			document.querySelector('.dp-field-scheduling-weekly-days').classList.remove('dp-control_hidden');
		}
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
	function setup$4() {
		loadDPAssets(['/com_dpcalendar/js/dpcalendar/layouts/block/datepicker.js', '/com_dpcalendar/js/dpcalendar/layouts/block/timepicker.js']);
		const check = DPCalendar.debounce(checkOverlapping, 2000);
		[].slice.call(document.querySelectorAll('#jform_start_date, #jform_start_date_time, #jform_end_date, #jform_end_date_time, #jform_rooms')).forEach((input) => {
			input.addEventListener('change', check);
		});
		if (document.getElementById('jform_catid')) {
			document.getElementById('jform_catid').addEventListener('change', check);
		}
		check();
		const allDayAdapter = (showDates) => {
			if (showDates) {
				document.getElementById('jform_start_date_time').style.display = 'inline-block';
				document.getElementById('jform_end_date_time').style.display = 'inline-block';
				return;
			}
			document.getElementById('jform_start_date_time').style.display = 'none';
			document.getElementById('jform_end_date_time').style.display = 'none';
		};
		[].slice.call(document.querySelectorAll('#jform_all_day input')).forEach((input) => {
			input.addEventListener('click', () => allDayAdapter(input.value == 0) && checkOverlapping());
		});
		const allDayField = document.querySelector('#jform_all_day');
		if (allDayField && allDayField.tagName.toLowerCase() == 'input') {
			allDayAdapter(allDayField.value == 0);
			return;
		}
		allDayAdapter(document.getElementById('jform_all_day0').checked
			|| (!document.getElementById('jform_all_day0').checked && !document.getElementById('jform_all_day1').checked));
	}
	function checkOverlapping() {
		const box = document.querySelector('.com-dpcalendar-eventform__overlapping');
		if (!box) {
			return;
		}
		box.style.display = 'none';
		loadDPAssets(['/com_dpcalendar/js/domurl/url.js'], () => {
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
					[].slice.call(document.querySelectorAll('.dp-button-apply, .button-apply, .dp-button-save, .button-save, .dp-button-save2new, .button-save-new, .dp-button-save2copy, .button-save-copy')).forEach((button) => {
						button.disabled = json.data.count > 0;
					});
				},
				DPCalendar.formToQueryString(document.querySelector('.com-dpcalendar-eventform__form'), 'input:not([name=task]), select') + (url.query.e_id ? '&id=' + url.query.e_id : '')
			);
		});
	}
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
	function setup$3() {
		const map = document.querySelector('.com-dpcalendar-eventform .dp-map');
		if (map != null) {
			map.addEventListener('dp-map-loaded', updateLocationFrame);
			watchElements([map]);
		}
		const rooms = document.querySelector('.com-dpcalendar-eventform .dp-field-rooms');
		if (rooms && rooms.querySelectorAll('option').length == 0) {
			rooms.style.display = 'none';
		}
		const geoComplete = document.querySelector('.com-dpcalendar-eventform #jform_location_lookup');
		if (!geoComplete) {
			return;
		}
		loadDPAssets(['/com_dpcalendar/js/dpcalendar/layouts/block/autocomplete.js'], () => DPCalendar.autocomplete.create(geoComplete));
		geoComplete.addEventListener('dp-autocomplete-select', (e) => {
			DPCalendar.request(
				'task=event.newlocation',
				(json) => {
					if (json.data.id == null || json.data.display == null) {
						return;
					}
					geoComplete.value = '';
					const select = document.getElementById('jform_location_ids');
					if ([].slice.call(select.selectedOptions).map((option) => option.value).indexOf(json.data.id) != -1) {
						return;
					}
					select.add(new Option(json.data.display, json.data.id ? json.data.id : json.data.display, false, true));
					if (rooms && rooms.querySelectorAll('option').length == 0) {
						rooms.style.display = 'none';
					}
					select.dpInternalTrigger = true;
					select.dispatchEvent(new Event('change'));
					select.dpInternalTrigger = false;
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
		const locationIds = document.getElementById('jform_location_ids');
		if (!locationIds) {
			return;
		}
		locationIds.addEventListener('change', (e) => {
			if (e.target.dpInternalTrigger === true || !document.getElementById('jform_rooms')) {
				return true;
			}
			if (isNaN(e.target.value)) {
				return;
			}
			const loader = document.querySelector('.dp-loader');
			if (loader) {
				loader.classList.remove('dp-loader_hidden');
			}
			Joomla.submitbutton('event.reload');
		});
	}
	function updateLocationFrame() {
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
			DPCalendar.Map.createMarker(map, { latitude: parts[0], longitude: parts[1], title: content });
		});
	}
	function setup$2() {
		if (parseInt(document.getElementById('jform_id').value) != 0) {
			return;
		}
		loadDPAssets(['/com_dpcalendar/js/domurl/url.js', '/com_dpcalendar/js/dpcalendar/layouts/block/autocomplete.js'], () => {
			const titleInput = document.getElementById('jform_title');
			DPCalendar.autocomplete.create(titleInput);
			titleInput.addEventListener('dp-autocomplete-select', (e) => {
				document.querySelector('input[name=template_event_id]').value = e.detail.value;
				Joomla.submitbutton('event.reloadfromevent');
			});
			const url = new Url();
			titleInput.addEventListener('dp-autocomplete-change', () => {
				loadDPAssets(['/com_dpcalendar/js/dpcalendar/dpcalendar.js'], () => {
					DPCalendar.request(
						'task=event.similar',
						(json) => DPCalendar.autocomplete.setItems(titleInput, json.data),
						DPCalendar.formToQueryString(document.querySelector('.com-dpcalendar-eventform__form'), 'input:not([name=task]), select') + '&id=' + url.query.e_id
					);
				});
			});
		});
	}
	function setup$1() {
		const captcha = document.querySelector('.dp-field-captcha');
		if (!captcha) {
			return;
		}
		document.querySelector('.com-dpcalendar-eventform__form').appendChild(captcha);
	}
	function setup() {
		if (document.getElementById('jform_catid')) {
			document.getElementById('jform_catid').addEventListener('change', (e) => {
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
		}
		[].slice.call(document.querySelectorAll('.com-dpcalendar-eventform__actions .dp-button-action')).forEach((button) => {
			button.addEventListener('click', () => Joomla.submitbutton('event.' + button.getAttribute('data-task')));
		});
		Joomla.submitbutton = (task) => {
			const loader = document.querySelector('.dp-loader');
			if (loader) {
				loader.classList.remove('dp-loader_hidden');
			}
			const form = document.getElementsByName('adminForm')[0];
			if (!form || (task.indexOf('reload') === -1 && task.indexOf('cancel') === -1 && task.indexOf('delete') === -1 && !document.formvalidator.isValid(form))) {
				return;
			}
			if (['save', 'save2new', 'apply'].indexOf(task.replace('event.', '')) === -1) {
				Joomla.submitform(task, form);
				return;
			}
			let updateString = Joomla.JText._('COM_DPCALENDAR_VIEW_EVENT_FORM_UPDATE_MODIFIED', '');
			if (updateString) {
				Joomla.getOptions('DPCalendar.event.form.seriesevents').forEach((event) => {
					updateString += '<p><a href="' + event.edit_link + '" target="_blank">' + event.title + ' ' + event.formatted_date + '</a></p>';
				});
			}
			if (document.querySelectorAll('.com-dpcalendar-eventform .dp-field_reset').length > 0) {
				updateString = Joomla.JText._('COM_DPCALENDAR_VIEW_EVENT_FORM_UPDATE_RESET', '');
			}
			ask(Joomla.JText._('COM_DPCALENDAR_VIEW_EVENT_SEND_TICKET_HOLDERS_NOFICATION', ''), true)
				.then((yes) => document.getElementById('jform_notify_changes').value = yes)
				.then(() => ask(updateString, updateString !== Joomla.JText._('COM_DPCALENDAR_VIEW_EVENT_FORM_UPDATE_RESET', '')))
				.then((yes) => document.getElementById('jform_update_modified').value = yes)
				.then(() => Joomla.submitform(task, form))
				.catch(() => {
					const loader = document.querySelector('.dp-loader');
					if (loader) {
						loader.classList.add('dp-loader_hidden');
					}
				});
		};
	}
	function ask(question, hasNo) {
		return new Promise(function (resolve, reject) {
			if (question === '') {
				resolve(0);
				return;
			}
			loadDPAssets(['/com_dpcalendar/js/tingle/tingle.js', '/com_dpcalendar/css/tingle/tingle.css'], () => {
				const modal = new tingle.modal({ footer: true, closeMethods: [], cssClass: ['dpcalendar-modal'] });
				modal.setContent(question);
				modal.addFooterBtn(Joomla.JText._('JYES'), 'dp-button', () => {
					modal.close();
					resolve(1);
				});
				if (hasNo) {
					modal.addFooterBtn(Joomla.JText._('JNO'), 'dp-button', () => {
						modal.close();
						resolve(0);
					});
				}
				modal.addFooterBtn(Joomla.JText._('JCANCEL'), 'dp-button', () => {
					modal.close();
					reject();
				});
				modal.open();
			});
		});
	}
	document.addEventListener('DOMContentLoaded', () => {
		loadDPAssets(['/com_dpcalendar/js/dpcalendar/dpcalendar.js', '/com_dpcalendar/js/dpcalendar/layouts/block/select.js'], () => {
			setup$5();
			setup$4();
			setup$3();
			setup$2();
			setup$1();
			setup();
			[].slice.call(document.querySelectorAll('.dp-field-start-date, .dp-field-end-date, .dp-field-rrule input')).forEach((input) => {
				input.addEventListener('change', (e) => {
					if ((input.closest('dp-field-start-date') || input.closest('dp-field-end-date')) && !input.dpPikaday) {
						return;
					}
					const parent = document.querySelector('.com-dpcalendar-eventform__original-warning');
					if (!parent) {
						return;
					}
					parent.classList.remove('com-dpcalendar-eventform__original-warning_reset');
					e.target.classList.remove('dp-field_reset');
					if (e.target.value == e.target.defaultValue) {
						return;
					}
					parent.classList.add('com-dpcalendar-eventform__original-warning_reset');
					e.target.classList.add('dp-field_reset');
				});
			});
			[].slice.call(document.querySelectorAll('.dp-field-all-day input')).forEach((input) => {
				input.addEventListener('click', (e) => {
					const parent = document.querySelector('.com-dpcalendar-eventform__original-warning');
					if (!parent) {
						return;
					}
					parent.classList.remove('com-dpcalendar-eventform__original-warning_reset');
					e.target.classList.remove('dp-field_reset');
					if (e.target.checked && e.target.getAttribute('checked') === 'checked') {
						return;
					}
					parent.classList.add('com-dpcalendar-eventform__original-warning_reset');
					e.target.classList.add('dp-field_reset');
				});
			});
		});
		if (Joomla.JText._('COM_DPCALENDAR_ONLY_AVAILABLE_SUBSCRIBERS', '')) {
			[].slice.call(document.querySelectorAll('.dp-field-scheduling .controls, .dp-tabs__tab-booking .controls, #com-dpcalendar-form-Content #booking .controls')).forEach((el) => {
				const option = document.createElement('span');
				option.className = 'dp-free-information-text';
				option.innerText = Joomla.JText._('COM_DPCALENDAR_ONLY_AVAILABLE_SUBSCRIBERS');
				el.appendChild(option);
			});
		}
	});
})();
