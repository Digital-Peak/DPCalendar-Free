/**
 * @package   DPCalendar
 * @copyright Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */
(function () {
	'use strict';
	function getCalendarIds(calendar, options, util) {
		let calendars;
		if (localStorage.getItem(options['storageId']) == null) {
			localStorage.setItem(options['storageId'], JSON.stringify(options['calendarIds']));
			calendars = options['calendarIds'];
		}
		if (!calendars) {
			calendars = JSON.parse(localStorage.getItem(options['storageId'])).filter((calId) => {
				return options['calendarIds'].findIndex((id) => String(id) === String(calId)) >= 0;
			});
		}
		return options['singleCalendarsFetch'] ? [calendars.join(',')] : calendars;
	}
	function attachStateListeners(calendar, options, util) {
		const calendarIds = getCalendarIds(calendar, options);
		const root = calendar.parentElement;
		const toggle = root.querySelector('.com-dpcalendar-calendar__toggle, .com-dpcalendar-calendar-timeline__toggle');
		if (toggle) {
			toggle.addEventListener('click', () => {
				util.slideToggle(root.querySelector('.com-dpcalendar-calendar__list, .com-dpcalendar-calendar-timeline__list'), (fadeIn) => {
					if (!fadeIn) {
						root.querySelector('[data-direction="up"]').classList.add('dp-toggle_hidden');
						root.querySelector('[data-direction="down"]').classList.remove('dp-toggle_hidden');
						return;
					}
					root.querySelector('[data-direction="up"]').classList.remove('dp-toggle_hidden');
					root.querySelector('[data-direction="down"]').classList.add('dp-toggle_hidden');
				});
			});
		}
		const calendars = [].slice.call(calendar.parentElement.querySelectorAll('.com-dpcalendar-calendar__calendars .dp-input-checkbox, .com-dpcalendar-calendar-timeline__calendars .dp-input-checkbox'));
		calendars.forEach((input) => {
			calendarIds.forEach((calId) => {
				if (calId == input.value) {
					input.checked = true;
				}
			});
			input.addEventListener('click', (event) => {
				const checkbox = event.target;
				const calId = checkbox.value;
				if (checkbox.checked) {
					calendar.dpCalendar.addEventSource({
						id: calId,
						events: (fetchInfo, successCallback, failureCallback) => {
							util.request(
								options['requestUrlRoot'] + '&ids=' + calId + '&date-start=' + fetchInfo.startStr + '&date-end=' + fetchInfo.endStr,
								(json) => successCallback(json.data),
								null,
								false
							);
						}
					});
					calendarIds.push(calId);
				} else {
					calendar.dpCalendar.getEventSources().forEach((eventSource) => {
						if (eventSource.id != calId) {
							return;
						}
						eventSource.remove();
					});
					calendarIds.forEach((calendarId, index) => {
						if (calendarId == calId) {
							calendarIds.splice(index, 1);
						}
					});
				}
				localStorage.setItem(options['storageId'], JSON.stringify(calendarIds));
			});
		});
		const toggleBoxes = document.querySelector('.com-dpcalendar-calendar__list-toggle .dp-input-checkbox, .com-dpcalendar-calendar-timeline__list-toggle .dp-input-checkbox');
		if (toggleBoxes) {
			toggleBoxes.addEventListener('click', () => {
				calendars.forEach((input) => {
					input.checked = !toggleBoxes.checked;
					input.click();
				});
			});
		}
	}
	function getMappings(options) {
		const viewMapping = [];
		viewMapping['month'] = 'dayGridMonth';
		viewMapping['week'] = 'timeGridWeek';
		viewMapping['day'] = 'timeGridDay';
		viewMapping['list'] = 'list';
		viewMapping['resyear'] = 'resourceTimelineYear';
		viewMapping['resmonth'] = 'resourceTimelineMonth';
		viewMapping['resweek'] = 'resourceTimelineWeek';
		viewMapping['resday'] = 'resourceTimelineDay';
		if (options['resources'] != null && options['resourceViews'] && options['resourceViews'].find((name) => name === 'week')) {
			viewMapping['week'] = 'resourceTimeGridWeek';
		}
		if (options['resources'] != null && options['resourceViews'] && options['resourceViews'].find((name) => name === 'day')) {
			viewMapping['day'] = 'resourceTimeGridDay';
		}
		return viewMapping;
	}
	function getReversMappings(options) {
		const viewMappingReverse = [];
		viewMappingReverse['dayGridMonth'] = 'month';
		viewMappingReverse['timeGridWeek'] = 'week';
		viewMappingReverse['timeGridDay'] = 'day';
		viewMappingReverse['list'] = 'list';
		viewMappingReverse['resourceTimelineYear'] = 'resyear';
		viewMappingReverse['resourceTimelineMonth'] = 'resmonth';
		viewMappingReverse['resourceTimelineWeek'] = 'resweek';
		viewMappingReverse['resourceTimelineDay'] = 'resday';
		if (options['resources'] != null) {
			viewMappingReverse['resourceTimeGridWeek'] = 'week';
			viewMappingReverse['resourceTimeGridDay'] = 'day';
		}
		return viewMappingReverse
	}
	function setup$5(calendar, options) {
		const viewMapping = getMappings(options);
		const viewMappingReverse = getReversMappings(options);
		if (options['use_hash']) {
			const consts = window.location.hash.replace(/&amp;/gi, '&').split('&');
			for (let i = 0; i < consts.length; i++) {
				if (consts[i].match('^#year')) {
					options['year'] = consts[i].substring(6);
				}
				if (consts[i].match('^month')) {
					options['month'] = consts[i].substring(6);
				}
				if (consts[i].match('^day')) {
					options['date'] = consts[i].substring(4);
				}
				if (consts[i].match('^view')) {
					options['initialView'] = consts[i].substring(5);
				}
			}
		}
		options['initialDate'] = new Date(
			options['year'] + '-' +
			DPCalendar.pad(parseInt(options['month']), 2) + '-' +
			DPCalendar.pad(options['date'], 2)
		);
		options['timeZone'] = Joomla.getOptions('DPCalendar.timezone');
		options['initialView'] = viewMapping[options['initialView']];
		if (document.body.clientWidth < options['screen_size_list_view'] && viewMappingReverse[options['initialView']] != 'day') {
			options['initialView'] = viewMapping['list'];
		}
		options['schedulerLicenseKey'] = 'GPL-My-Project-Is-Open-Source';
		options['eventDisplay'] = 'block';
		options['progressiveEventRendering'] = false;
		options['weekNumberTitle'] = '';
		options['weekNumberFormat'] = { week: 'numeric' };
		options['moreLinkContent'] = Joomla.JText._('COM_DPCALENDAR_VIEW_CALENDAR_VIEW_TEXTS_MORE');
		options['allDayContent'] = Joomla.JText._('COM_DPCALENDAR_VIEW_CALENDAR_ALL_DAY');
		options['buttonText'] = {
			today: Joomla.JText._('COM_DPCALENDAR_VIEW_CALENDAR_TOOLBAR_TODAY'),
			year: Joomla.JText._('COM_DPCALENDAR_VIEW_CALENDAR_VIEW_YEAR'),
			month: Joomla.JText._('COM_DPCALENDAR_VIEW_CALENDAR_VIEW_MONTH'),
			week: Joomla.JText._('COM_DPCALENDAR_VIEW_CALENDAR_VIEW_WEEK'),
			day: Joomla.JText._('COM_DPCALENDAR_VIEW_CALENDAR_VIEW_DAY'),
			list: Joomla.JText._('COM_DPCALENDAR_VIEW_CALENDAR_VIEW_LIST')
		};
		options['listTexts'] = {
			until: Joomla.JText._('COM_DPCALENDAR_VIEW_CALENDAR_VIEW_TEXTS_UNTIL'),
			past: Joomla.JText._('COM_DPCALENDAR_VIEW_CALENDAR_VIEW_TEXTS_PAST'),
			today: Joomla.JText._('COM_DPCALENDAR_VIEW_CALENDAR_VIEW_TEXTS_TODAY'),
			tomorrow: Joomla.JText._('COM_DPCALENDAR_VIEW_CALENDAR_VIEW_TEXTS_TOMORROW'),
			thisWeek: Joomla.JText._('COM_DPCALENDAR_VIEW_CALENDAR_VIEW_TEXTS_THIS_WEEK'),
			nextWeek: Joomla.JText._('COM_DPCALENDAR_VIEW_CALENDAR_VIEW_TEXTS_NEXT_WEEK'),
			thisMonth: Joomla.JText._('COM_DPCALENDAR_VIEW_CALENDAR_VIEW_TEXTS_THIS_MONTH'),
			nextMonth: Joomla.JText._('COM_DPCALENDAR_VIEW_CALENDAR_VIEW_TEXTS_NEXT_MONTH'),
			future: Joomla.JText._('COM_DPCALENDAR_VIEW_CALENDAR_VIEW_TEXTS_FUTURE'),
			week: Joomla.JText._('COM_DPCALENDAR_VIEW_CALENDAR_VIEW_TEXTS_WEEK')
		};
	}
	function setup$4(calendar, options) {
		options['eventDragStart'] = (info) => {
			if (info.el._tippy) {
				info.el._tippy.destroy();
			}
		};
		options['eventDrop'] = (info) => {
			if (info.event.resourceId) {
				info.revert();
				return false;
			}
			DPCalendar.request(
				'task=event.move&Itemid=' + Joomla.getOptions('DPCalendar.itemid'),
				(json) => {
					if (json.data.url) {
						info.event.setProp('url', json.data.url);
					}
					if (json.data.description) {
						info.event.setExtendedProp('description', json.data.description);
					}
					if (!json.success) {
						info.revert();
						return;
					}
					if (json.messages == null) {
						info.revert();
						return;
					}
					for (let type in json.messages) {
						if (type != 'message') {
							info.revert();
							return;
						}
					}
				},
				'id=' + info.event.id + '&minutes=' + dayjs.duration(info.delta).asMinutes() + '&allDay=' + info.event.allDay
			);
		};
		options['eventResize'] = (info) => {
			if (info.el._tippy) {
				info.el._tippy.destroy();
			}
			DPCalendar.request(
				'task=event.move&Itemid=' + Joomla.getOptions('DPCalendar.itemid'),
				(json) => {
					if (json.data.url) {
						info.event.setProp('url', json.data.url);
					}
					if (json.data.description) {
						info.event.setExtendedProp('description', json.data.description);
					}
					if (!json.success) {
						info.revert();
						return;
					}
					if (json.messages == null) {
						info.revert();
						return;
					}
					for (let type in json.messages) {
						if (type != 'message') {
							info.revert();
							return;
						}
					}
				},
				'id=' + info.event.id + '&minutes=' + dayjs.duration(info.endDelta).asMinutes() + '&onlyEnd=1&allDay=' + info.event.allDay
			);
		};
	}
	function setup$3(calendar, options) {
		if (!options['use_hash']) {
			return;
		}
		const viewMapping = getMappings(options);
		const viewMappingReverse = getReversMappings(options);
		window.addEventListener('hashchange', () => {
			const today = new Date();
			let tmpYear = today.getUTCFullYear();
			let tmpMonth = today.getUTCMonth() + 1;
			let tmpDay = today.getUTCDate();
			let tmpView = viewMappingReverse[options['initialView']];
			window.location.hash.replace(/&amp;/gi, '&').split('&').forEach((value) => {
				if (value.match('^#year')) {
					tmpYear = value.substring(6);
				}
				if (value.match('^month')) {
					tmpMonth = value.substring(6) - 1;
				}
				if (value.match('^day')) {
					tmpDay = value.substring(4);
				}
				if (value.match('^view')) {
					tmpView = value.substring(5);
				}
			});
			const date = new Date(Date.UTC(tmpYear, tmpMonth, tmpDay, 0, 0, 0));
			const d = calendar.dpCalendar.getDate();
			const view = calendar.dpCalendar.view;
			if (date.getUTCFullYear() != d.getUTCFullYear() || date.getUTCMonth() != d.getUTCMonth() || date.getUTCDate() != d.getUTCDate()) {
				calendar.dpCalendar.gotoDate(date);
			}
			if (view.type != viewMapping[tmpView]) {
				calendar.dpCalendar.changeView(viewMapping[tmpView]);
			}
		});
	}
	function updateHash(d, view, options) {
		const newHash = 'year=' + d.getUTCFullYear() + '&month=' + (d.getUTCMonth() + 1) + '&day=' + d.getUTCDate() + '&view=' + view;
		if (options['use_hash'] && window.location.hash.replace(/&amp;/gi, "&").replace('#', '') != newHash) {
			window.location.hash = newHash;
		}
	}
	function setup$2(calendar, options) {
		const viewMapping = getMappings(options);
		options['eventClick'] = (info) => {
			info.jsEvent.preventDefault();
			if (info.jsEvent.currentTarget._tippy) {
				info.jsEvent.currentTarget._tippy.hide();
			}
			if (options['show_event_as_popup'] == 2) {
				return;
			}
			if (options['show_event_as_popup'] == 1) {
				DPCalendar.modal(info.event.url, options.popupWidth, options.popupHeight, (frame) => {
					if (frame.contentWindow.location.href.indexOf('view=form') > 0) {
						DPCalendar.request('index.php?option=com_dpcalendar&task=event.checkin&e_id=' + info.event.id + '&' + Joomla.getOptions('csrf.token') + '=1');
					}
					const innerDoc = frame.contentDocument || frame.contentWindow.document;
					if (!innerDoc.getElementById('system-message-container') || innerDoc.getElementById('system-message-container').children.length < 1) {
						return;
					}
					calendar.dpCalendar.refetchEvents();
				});
				return;
			}
			window.location = DPCalendar.encode(info.event.url);
		};
		options['dateClick'] = (info) => {
			const form = calendar.parentElement.querySelector('.dp-quickadd .dp-form');
			let date = dayjs.utc(info.date);
			if (form) {
				info.jsEvent.preventDefault();
				if (info.view.type == viewMapping['month']) {
					date = date.hour(8);
					date = date.minute(0);
				}
				let start = form.querySelector('#jform_start_date');
				start.value = date.format(start.getAttribute('data-format'));
				start.actualDate = start.value;
				start.setAttribute('data-date', date.format('YYYY-MM-DD'));
				start.dpPikaday.setDate(date.format('YYYY-MM-DD HH:mm'));
				start = form.querySelector('#jform_start_date_time');
				start.value = date.format(start.getAttribute('data-format'));
				start.actualDate = start.value;
				date = date.add(1, 'hours');
				let end = form.querySelector('#jform_end_date');
				end.value = date.format(end.getAttribute('data-format'));
				end.actualDate = end.value;
				end.setAttribute('data-date', date.format('YYYY-MM-DD'));
				end.dpPikaday.setDate(date.format('YYYY-MM-DD HH:mm'));
				end = form.querySelector('#jform_end_date_time');
				end.value = date.format(end.getAttribute('data-format'));
				end.actualDate = end.value;
				if (info.resource) {
					const parts = info.resource.id.split('-');
					form.querySelector('input[name="jform[location_ids][]"]').value = [parts[0]];
					if (parts.length > 1) {
						form.querySelector('input[name="jform[rooms][]"]').value = [info.resource.id];
					}
				}
				if (options['event_create_form'] == 1 && window.innerWidth > 600) {
					form.parentElement.style.display = 'block';
					if (!info.jsEvent.target.dpPopper) {
						info.jsEvent.target.dpPopper = Popper.createPopper(info.jsEvent.target, form.parentElement, {
							onFirstUpdate: (state) => state.elements.popper.querySelector('#jform_title').focus()
						});
					}
					info.jsEvent.target.dpPopper.forceUpdate();
				} else {
					form.querySelector('input[name=task]').value = '';
					form.submit();
				}
				return;
			}
			if (options['headerToolbar'].right.indexOf(viewMapping['day']) > 0) {
				updateHash(info.date, 'day', options);
			}
		};
		document.body.addEventListener('click', (e) => {
			if (!e.target || !e.target.closest('.dp-event-tooltip__action-delete')) {
				return true;
			}
			if (confirm(Joomla.JText._('COM_DPCALENDAR_CONFIRM_DELETE'))) {
				return true;
			}
			e.preventDefault();
			return false;
		});
	}
	function setup$1(calendar, options) {
		options['customButtons'] = {};
		if (options['headerToolbar'].left.indexOf('datepicker')) {
			options['customButtons'].datepicker = {
				text: Joomla.JText._('COM_DPCALENDAR_VIEW_CALENDAR_SHOW_DATEPICKER'),
				icon: 'icon-calendar',
				click: () => {
					loadDPAssets(['/com_dpcalendar/js/pikaday/pikaday.js', '/com_dpcalendar/css/pikaday/pikaday.css'], () => {
						const button = document.querySelector('.fc-datepicker-button');
						let input = button.querySelector('input');
						if (!input) {
							input = document.createElement('input');
							input.setAttribute('type', 'hidden');
							input.id = 'datepicker-input';
							button.appendChild(input);
							const names = Joomla.getOptions('DPCalendar.calendar.names');
							input.dpPicker = new Pikaday({
								field: input,
								trigger: button,
								firstDay: options['firstDay'],
								i18n: {
									months: names['monthNames'],
									weekdays: names['dayNames'],
									weekdaysShort: names['dayNamesShort']
								},
								onSelect: (d) => {
									const date = new Date(Date.UTC(d.getFullYear(), d.getMonth(), d.getDate(), 0, 0, 0));
									updateHash(date, getReversMappings(options)[calendar.dpCalendar.view.type], options);
								}
							});
						}
						input.dpPicker.show();
					});
				}
			};
		}
		if (options['headerToolbar'].left.indexOf('print')) {
			options['customButtons'].print = {
				text: Joomla.JText._('COM_DPCALENDAR_VIEW_CALENDAR_TOOLBAR_PRINT'),
				icon: 'icon-print',
				click: () => {
					const printWindow = window.open();
					loadDPAssets(['/com_dpcalendar/js/domurl/url.js'], () => {
						const url = new Url();
						url.query.layout = 'print' + (options['resources'] ? 'timeline' : '');
						url.query.tmpl = 'component';
						printWindow.location.href = url.decode(url.toString());
						printWindow.focus();
					});
				}
			};
		}
		if (options['headerToolbar'].left.indexOf('add') && options['event_create_url']) {
			options['customButtons'].add = {
				text: Joomla.JText._('COM_DPCALENDAR_VIEW_CALENDAR_TOOLBAR_ADD'),
				icon: 'icon-add',
				click: () => location.href = options['event_create_url']
			};
		}
		if (options['headerToolbar'].left.indexOf('fullscreen')) {
			options['customButtons'].fullscreen_open = {
				icon: 'icon-fullscreen',
				text: Joomla.JText._('COM_DPCALENDAR_VIEW_CALENDAR_TOOLBAR_FULLSCREEN_OPEN'),
				click: () => {
					document.querySelector('.com-dpcalendar-calendar').requestFullscreen();
					document.querySelector('.fc-fullscreen_open-button').style.setProperty('display', 'none', 'important');
					document.querySelector('.fc-fullscreen_close-button').style.setProperty('display', 'inherit', 'important');
				}
			};
			options['customButtons'].fullscreen_close = {
				icon: 'icon-fullscreen',
				text: Joomla.JText._('COM_DPCALENDAR_VIEW_CALENDAR_TOOLBAR_FULLSCREEN_CLOSE'),
				click: () => {
					if (document.fullscreenElement) {
						document.exitFullscreen();
					}
					document.querySelector('.fc-fullscreen_open-button').style.setProperty('display', 'inherit', 'important');
					document.querySelector('.fc-fullscreen_close-button').style.setProperty('display', 'none', 'important');
				}
			};
		}
	}
	function adaptIcons(calendar) {
		const iconHandler = (iconName, buttonName) => {
			const icon = calendar.parentElement.querySelector('.dp-icon_' + iconName);
			const button = calendar.parentElement.querySelector('.fc-' + buttonName + '-button .fc-icon');
			if (icon === null || button === null) {
				return;
			}
			button.innerHTML = icon.outerHTML;
		};
		iconHandler('angle-left', 'prev');
		iconHandler('angle-right', 'next');
		iconHandler('calendar-days', 'datepicker');
		iconHandler('up-right-and-down-left-from-center', 'fullscreen_open');
		iconHandler('down-left-and-up-right-to-center', 'fullscreen_close');
		iconHandler('print', 'print');
		iconHandler('plus', 'add');
	}
	function setup(calendar, options) {
		calendar.dpEventMarkerSet = [];
		options['viewClassNames'] = (info) => {
			const map = calendar.parentElement.querySelector('.dp-map');
			if (!DPCalendar.Map || map == null || !options['show_map']) {
				return;
			}
			calendar.dpEventMarkerSet = [];
			DPCalendar.Map.clearMarkers(map);
		};
		options['eventContent'] = (info) => {
			let content = '<span class="dp-event__time">' + info.timeText + '</span><span class="dp-event__title">' + info.event.title + '</span>';
			if (info.event.extendedProps.capacity !== undefined && info.event.extendedProps.capacity != 0) {
				content += '<span class="dp-event__capacity"><svg class="dp-event__capacity-icon"><use href="#dp-icon-users"/></svg>';
				content += '<span class="dp-event__capacity-text">';
				if (info.event.extendedProps.capacity === null) {
					content += Joomla.JText._('COM_DPCALENDAR_FIELD_CAPACITY_UNLIMITED');
				} else {
					content += info.event.extendedProps.capacity_used + '/' + info.event.extendedProps.capacity;
				}
				content += '</span>';
				content += '</span>';
			}
			if (info.view.type == 'list') {
				content = '<a href="' + info.event.url + '">' + content + '</a>';
			}
			return { html: content };
		};
		options['eventDidMount'] = (info) => {
			if (info.event.view_class) {
				info.el.classList.add(info.event.view_class);
			}
			if (info.view.type != 'list') {
				info.el.style.fill = info.event.textColor;
			}
			let desc = info.event.extendedProps.description;
			if (desc) {
				desc = desc.replace('task=event.delete', 'task=event.delete&urlhash=' + encodeURIComponent(window.location.hash));
				desc = desc.replace('task=event.edit', 'task=event.edit&urlhash=' + encodeURIComponent(window.location.hash));
				if (!info.isMirror) {
					loadDPAssets(['/com_dpcalendar/js/tippy/tippy.js', '/com_dpcalendar/css/tippy/tippy.css'], () => {
						const content = document.createElement('div');
						content.innerHTML = desc;
						tippy(info.el, {
							interactive: true,
							delay: 100,
							arrow: true,
							content: desc,
							allowHTML: true,
							ignoreAttributes: true,
							appendTo: document.body,
							theme: 'light',
							touch: false,
							onShow: (instance) => instance.popper.querySelector('div[role="tooltip"]').classList.add('show'),
							popperOptions: { modifiers: [{ name: 'preventOverflow', enabled: false }, { name: 'hide', enabled: false }] }
						});
					});
				}
			}
			const map = calendar.parentElement.querySelector('.dp-map');
			if (!DPCalendar.Map || map == null || !options['show_map'] || !info.event.extendedProps.location) {
				return;
			}
			info.event.extendedProps.location.forEach((location) => {
				if (calendar.dpEventMarkerSet[info.event.id + ' ' + location.latitude + ' ' + location.longitude]) {
					return;
				}
				const locationData = JSON.parse(JSON.stringify(location));
				locationData.title = info.event.title;
				locationData.color = info.event.backgroundColor;
				if (info.event.url && desc) {
					desc = desc.replace(info.event.title, '<a href="' + info.event.url + '">' + info.event.title + '</a>');
				}
				locationData.description = desc;
				calendar.dpEventMarkerSet[info.event.id + ' ' + location.latitude + ' ' + location.longitude] = locationData;
				DPCalendar.Map.createMarker(map, locationData);
			});
		};
		options['eventDataTransform'] = (event) => {
			if (event.allDay) {
				let end = dayjs(event.end);
				end = end.add(1, 'day');
				event.end = end.format('YYYY-MM-DD');
			}
			return event;
		};
		options['loading'] = (bool) => {
			if (bool) {
				calendar.parentElement.parentElement.querySelector('.dp-loader').classList.remove('dp-loader_hidden');
			} else {
				calendar.parentElement.parentElement.querySelector('.dp-loader').classList.add('dp-loader_hidden');
			}
		};
		options['eventSources'] = [];
		getCalendarIds(calendar, options, DPCalendar).forEach((calId, index) => {
			options['eventSources'][index] = {
				id: calId,
				events: (fetchInfo, successCallback, failureCallback) => {
					DPCalendar.request(
						options['requestUrlRoot'] + '&ids=' + calId + '&date-start=' + fetchInfo.startStr + '&date-end=' + fetchInfo.endStr,
						(json) => successCallback(json.data),
						null,
						false,
						'GET'
					);
				}
			};
		});
	}
	const safeAdd = (x, y) => {
		let lsw = (x & 0xFFFF) + (y & 0xFFFF);
		return (((x >> 16) + (y >> 16) + (lsw >> 16)) << 16) | (lsw & 0xFFFF)
	};
	const bitRotateLeft = (num, cnt) => (num << cnt) | (num >>> (32 - cnt));
	const md5cmn = (q, a, b, x, s, t) => safeAdd(bitRotateLeft(safeAdd(safeAdd(a, q), safeAdd(x, t)), s), b),
		md5ff = (a, b, c, d, x, s, t) => md5cmn((b & c) | ((~b) & d), a, b, x, s, t),
		md5gg = (a, b, c, d, x, s, t) => md5cmn((b & d) | (c & (~d)), a, b, x, s, t),
		md5hh = (a, b, c, d, x, s, t) => md5cmn(b ^ c ^ d, a, b, x, s, t),
		md5ii = (a, b, c, d, x, s, t) => md5cmn(c ^ (b | (~d)), a, b, x, s, t);
	const firstChunk = (chunks, x, i) => {
			let [a, b, c, d] = chunks;
			a = md5ff(a, b, c, d, x[i + 0], 7, -680876936);
			d = md5ff(d, a, b, c, x[i + 1], 12, -389564586);
			c = md5ff(c, d, a, b, x[i + 2], 17, 606105819);
			b = md5ff(b, c, d, a, x[i + 3], 22, -1044525330);
			a = md5ff(a, b, c, d, x[i + 4], 7, -176418897);
			d = md5ff(d, a, b, c, x[i + 5], 12, 1200080426);
			c = md5ff(c, d, a, b, x[i + 6], 17, -1473231341);
			b = md5ff(b, c, d, a, x[i + 7], 22, -45705983);
			a = md5ff(a, b, c, d, x[i + 8], 7, 1770035416);
			d = md5ff(d, a, b, c, x[i + 9], 12, -1958414417);
			c = md5ff(c, d, a, b, x[i + 10], 17, -42063);
			b = md5ff(b, c, d, a, x[i + 11], 22, -1990404162);
			a = md5ff(a, b, c, d, x[i + 12], 7, 1804603682);
			d = md5ff(d, a, b, c, x[i + 13], 12, -40341101);
			c = md5ff(c, d, a, b, x[i + 14], 17, -1502002290);
			b = md5ff(b, c, d, a, x[i + 15], 22, 1236535329);
			return [a, b, c, d]
		},
		secondChunk = (chunks, x, i) => {
			let [a, b, c, d] = chunks;
			a = md5gg(a, b, c, d, x[i + 1], 5, -165796510);
			d = md5gg(d, a, b, c, x[i + 6], 9, -1069501632);
			c = md5gg(c, d, a, b, x[i + 11], 14, 643717713);
			b = md5gg(b, c, d, a, x[i], 20, -373897302);
			a = md5gg(a, b, c, d, x[i + 5], 5, -701558691);
			d = md5gg(d, a, b, c, x[i + 10], 9, 38016083);
			c = md5gg(c, d, a, b, x[i + 15], 14, -660478335);
			b = md5gg(b, c, d, a, x[i + 4], 20, -405537848);
			a = md5gg(a, b, c, d, x[i + 9], 5, 568446438);
			d = md5gg(d, a, b, c, x[i + 14], 9, -1019803690);
			c = md5gg(c, d, a, b, x[i + 3], 14, -187363961);
			b = md5gg(b, c, d, a, x[i + 8], 20, 1163531501);
			a = md5gg(a, b, c, d, x[i + 13], 5, -1444681467);
			d = md5gg(d, a, b, c, x[i + 2], 9, -51403784);
			c = md5gg(c, d, a, b, x[i + 7], 14, 1735328473);
			b = md5gg(b, c, d, a, x[i + 12], 20, -1926607734);
			return [a, b, c, d]
		},
		thirdChunk = (chunks, x, i) => {
			let [a, b, c, d] = chunks;
			a = md5hh(a, b, c, d, x[i + 5], 4, -378558);
			d = md5hh(d, a, b, c, x[i + 8], 11, -2022574463);
			c = md5hh(c, d, a, b, x[i + 11], 16, 1839030562);
			b = md5hh(b, c, d, a, x[i + 14], 23, -35309556);
			a = md5hh(a, b, c, d, x[i + 1], 4, -1530992060);
			d = md5hh(d, a, b, c, x[i + 4], 11, 1272893353);
			c = md5hh(c, d, a, b, x[i + 7], 16, -155497632);
			b = md5hh(b, c, d, a, x[i + 10], 23, -1094730640);
			a = md5hh(a, b, c, d, x[i + 13], 4, 681279174);
			d = md5hh(d, a, b, c, x[i], 11, -358537222);
			c = md5hh(c, d, a, b, x[i + 3], 16, -722521979);
			b = md5hh(b, c, d, a, x[i + 6], 23, 76029189);
			a = md5hh(a, b, c, d, x[i + 9], 4, -640364487);
			d = md5hh(d, a, b, c, x[i + 12], 11, -421815835);
			c = md5hh(c, d, a, b, x[i + 15], 16, 530742520);
			b = md5hh(b, c, d, a, x[i + 2], 23, -995338651);
			return [a, b, c, d]
		},
		fourthChunk = (chunks, x, i) => {
			let [a, b, c, d] = chunks;
			a = md5ii(a, b, c, d, x[i], 6, -198630844);
			d = md5ii(d, a, b, c, x[i + 7], 10, 1126891415);
			c = md5ii(c, d, a, b, x[i + 14], 15, -1416354905);
			b = md5ii(b, c, d, a, x[i + 5], 21, -57434055);
			a = md5ii(a, b, c, d, x[i + 12], 6, 1700485571);
			d = md5ii(d, a, b, c, x[i + 3], 10, -1894986606);
			c = md5ii(c, d, a, b, x[i + 10], 15, -1051523);
			b = md5ii(b, c, d, a, x[i + 1], 21, -2054922799);
			a = md5ii(a, b, c, d, x[i + 8], 6, 1873313359);
			d = md5ii(d, a, b, c, x[i + 15], 10, -30611744);
			c = md5ii(c, d, a, b, x[i + 6], 15, -1560198380);
			b = md5ii(b, c, d, a, x[i + 13], 21, 1309151649);
			a = md5ii(a, b, c, d, x[i + 4], 6, -145523070);
			d = md5ii(d, a, b, c, x[i + 11], 10, -1120210379);
			c = md5ii(c, d, a, b, x[i + 2], 15, 718787259);
			b = md5ii(b, c, d, a, x[i + 9], 21, -343485551);
			return [a, b, c, d]
		};
	const binlMD5 = (x, len) => {
		x[len >> 5] |= 0x80 << (len % 32);
		x[(((len + 64) >>> 9) << 4) + 14] = len;
		let commands = [firstChunk, secondChunk, thirdChunk, fourthChunk],
			initialChunks = [
				1732584193,
				-271733879,
				-1732584194,
				271733878
			];
		return Array.from({length: Math.floor(x.length / 16) + 1}, (v, i) => i * 16)
			.reduce((chunks, i) => commands
				.reduce((newChunks, apply) => apply(newChunks, x, i), chunks.slice())
				.map((chunk, index) => safeAdd(chunk, chunks[index])), initialChunks)
	};
	const binl2rstr = input => Array(input.length * 4).fill(8).reduce((output, k, i) => output + String.fromCharCode((input[(i * k) >> 5] >>> ((i * k) % 32)) & 0xFF), '');
	const rstr2binl = input => Array.from(input).map(i => i.charCodeAt(0)).reduce((output, cc, i) => {
		let resp = output.slice();
		resp[(i * 8) >> 5] |= (cc & 0xFF) << ((i * 8) % 32);
		return resp
	}, []);
	const rstrMD5 = string => binl2rstr(binlMD5(rstr2binl(string), string.length * 8));
	const strHMACMD5 = (key, data) => {
		let bkey = rstr2binl(key),
			ipad = Array(16).fill(undefined ^ 0x36363636),
			opad = Array(16).fill(undefined ^ 0x5C5C5C5C);
		if (bkey.length > 16) {
			bkey = binlMD5(bkey, key.length * 8);
		}
		bkey.forEach((k, i) => {
			ipad[i] = k ^ 0x36363636;
			opad[i] = k ^ 0x5C5C5C5C;
		});
		return binl2rstr(binlMD5(opad.concat(binlMD5(ipad.concat(rstr2binl(data)), 512 + data.length * 8)), 512 + 128))
	};
	const rstr2hex = input => {
		const hexTab = (pos) => '0123456789abcdef'.charAt(pos);
		return Array.from(input).map(c => c.charCodeAt(0)).reduce((output, x, i) => output + hexTab((x >>> 4) & 0x0F) + hexTab(x & 0x0F), '')
	};
	const str2rstrUTF8 = unicodeString => {
		if (typeof unicodeString !== 'string') throw new TypeError('parameter ‘unicodeString’ is not a string');
		const cc = c => c.charCodeAt(0);
		return unicodeString
			.replace(/[\u0080-\u07ff]/g,
				c => String.fromCharCode(0xc0 | cc(c) >> 6, 0x80 | cc(c) & 0x3f))
			.replace(/[\u0800-\uffff]/g,
				c => String.fromCharCode(0xe0 | cc(c) >> 12, 0x80 | cc(c) >> 6 & 0x3F, 0x80 | cc(c) & 0x3f))
	};
	const rawMD5 = s => rstrMD5(str2rstrUTF8(s));
	const hexMD5 = s => rstr2hex(rawMD5(s));
	const rawHMACMD5 = (k, d) => strHMACMD5(str2rstrUTF8(k), str2rstrUTF8(d));
	const hexHMACMD5 = (k, d) => rstr2hex(rawHMACMD5(k, d));
	var md5 = (string, key, raw) => {
		if (!key) {
			if (!raw) {
				return hexMD5(string)
			}
			return rawMD5(string)
		}
		if (!raw) {
			return hexHMACMD5(key, string)
		}
		return rawHMACMD5(key, string)
	};
	function createCalendar(calendar, options) {
		const assets = ['/com_dpcalendar/js/popper/popper.js'];
		if (!window.Intl && typeof window.Intl !== 'object') {
			assets.push('/com_dpcalendar/js/polyfill/intl.js');
		}
		if (options['resources']) {
			assets.push('/com_dpcalendar/js/scheduler/scheduler.js');
		} else {
			assets.push('/com_dpcalendar/js/fullcalendar/fullcalendar.js');
		}
		loadDPAssets(assets, () => {
			calendar.parentElement.parentElement.querySelector('.dp-loader').classList.add('dp-loader_hidden');
			const viewMapping = getMappings(options);
			const viewMappingReverse = getReversMappings(options);
			options['storageId'] = calendar.getAttribute('data-options') + '-calendar-state-' + md5(options['calendarIds'].toString());
			setup$5(calendar, options);
			setup$4(calendar, options);
			setup$2(calendar, options);
			setup$1(calendar, options);
			setup$3(calendar, options);
			setup(calendar, options);
			attachStateListeners(calendar, options, DPCalendar);
			const names = Joomla.getOptions('DPCalendar.calendar.names');
			dayjs.updateLocale('en', {
				months: names['monthNames'],
				monthsShort: names['monthNamesShort'],
				weekdays: names['dayNames'],
				weekdaysShort: names['dayNamesShort'],
				weekdaysMin: names['dayNamesMin']
			});
			const views = options['views'];
			options['views'] = {};
			Object.keys(views).forEach((view) => {
				const titleFormat = views[view]['titleFormat'];
				views[view]['titleFormat'] = (date) => {
					if (view == 'list' || view == 'week' || view == 'resweek') {
						return dayjs(date.start.array).format(titleFormat) + ' - ' + dayjs(date.end.array).format(titleFormat);
					}
					return dayjs(date.start.array).format(titleFormat);
				};
				options['views'][viewMapping[view]] = views[view];
				if (views[view]['slotDuration'] && views[view]['slotDuration'].indexOf('{') > -1) {
					views[view]['slotDuration'] = JSON.parse(views[view]['slotDuration']);
				}
				if (views[view]['slotLabelInterval'] && views[view]['slotLabelInterval'].indexOf('{') > -1) {
					views[view]['slotLabelInterval'] = JSON.parse(views[view]['slotLabelInterval']);
				}
			});
			if (options['headerToolbar']['right']) {
				let headers = '';
				options['headerToolbar']['right'].split(',').forEach((view, index) => {
					headers += (index != 0 ? ',' : '') + viewMapping[view];
				});
				options['headerToolbar']['right'] = headers;
			}
			options['datesSet'] = (info) => {
				updateHash(calendar.dpCalendar.getDate(), viewMappingReverse[info.view.type], options);
				adaptIcons(calendar);
			};
			const cal = new FullCalendar.Calendar(calendar, options);
			calendar.dpCalendar = cal;
			cal.render();
			calendar.dispatchEvent(new CustomEvent('dp-calendar-loaded', { detail: { calendar: cal } }));
		});
	}
	[].slice.call(document.querySelectorAll('.dp-calendar')).forEach((el) => {
		if (!el.getAttribute('data-options')) {
			return;
		}
		loadDPAssets(['/com_dpcalendar/js/dayjs/dayjs.js', '/com_dpcalendar/js/dpcalendar/dpcalendar.js'], () =>
			createCalendar(el, Joomla.getOptions(el.getAttribute('data-options')))
		);
	});
})();
