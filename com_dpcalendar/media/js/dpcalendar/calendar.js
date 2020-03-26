(function () {
	'use strict';

	/**
	 * @package   DPCalendar
	 * @author    Digital Peak http://www.digital-peak.com
	 * @copyright Copyright (C) 2007 - 2020 Digital Peak. All rights reserved.
	 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
	 */

	const DPCalendar = window.DPCalendar || {};
	window.DPCalendar = DPCalendar;

	document.addEventListener('DOMContentLoaded', () => {
		[].slice.call(document.querySelectorAll('.dp-calendar')).forEach((el) => {
			if (!el.getAttribute('data-options')) {
				return;
			}

			let options = Joomla.getOptions(el.getAttribute('data-options'));
			options = Object.assign(options, el.dataset);

			if (typeof options.hiddenDays == 'string') {
				options.hiddenDays = JSON.parse(options.hiddenDays);
			}

			DPCalendar.createCalendar(el, options);
		});
	});

	DPCalendar.createCalendar = (calendar, options) => {
		calendar.parentElement.parentElement.querySelector('.dp-loader').classList.add('dp-loader_hidden');
		const names = Joomla.getOptions('DPCalendar.calendar.names');

		const viewMapping = [];
		viewMapping['month'] = 'dayGridMonth';
		viewMapping['week'] = 'timeGridWeek';
		viewMapping['day'] = 'timeGridDay';
		viewMapping['list'] = 'list';
		viewMapping['resyear'] = 'resourceTimelineYear';
		viewMapping['resmonth'] = 'resourceTimelineMonth';
		viewMapping['resweek'] = 'resourceTimelineWeek';
		viewMapping['resday'] = 'resourceTimelineDay';
		//BC < 7.2
		viewMapping['agendaWeek'] = 'timeGridWeek';
		viewMapping['agendaDay'] = 'timeGridDay';

		const viewMappingReverse = [];
		viewMappingReverse['dayGridMonth'] = 'month';
		viewMappingReverse['timeGridWeek'] = 'week';
		viewMappingReverse['timeGridDay'] = 'day';
		viewMappingReverse['list'] = 'list';
		viewMappingReverse['resourceTimelineYear'] = 'resyear';
		viewMappingReverse['resourceTimelineMonth'] = 'resmonth';
		viewMappingReverse['resourceTimelineWeek'] = 'resweek';
		viewMappingReverse['resourceTimelineDay'] = 'resday';
		//BC < 7.2
		viewMappingReverse['agendaWeek'] = 'week';
		viewMappingReverse['agendaDay'] = 'day';

		// If there are resources use the proper timegrid views
		if (options['resources'] != null) {
			viewMapping['week'] = 'resourceTimeGridWeek';
			viewMapping['day'] = 'resourceTimeGridDay';
			viewMappingReverse['resourceTimeGridWeek'] = 'week';
			viewMappingReverse['resourceTimeGridDay'] = 'day';
		}

		if (options['use_hash']) {
			// Parsing the hash
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
					options['defaultView'] = consts[i].substring(5);
				}
			}

			// Listening for hash/url changes
			window.addEventListener('hashchange', () => {
				const today = new Date();
				let tmpYear = today.getUTCFullYear();
				let tmpMonth = today.getUTCMonth() + 1;
				let tmpDay = today.getUTCDate();
				let tmpView = viewMappingReverse[options['defaultView']];
				let consts = window.location.hash.replace(/&amp;/gi, '&').split('&');
				for (let i = 0; i < consts.length; i++) {
					if (consts[i].match('^#year')) {
						tmpYear = consts[i].substring(6);
					}
					if (consts[i].match('^month')) {
						tmpMonth = consts[i].substring(6) - 1;
					}
					if (consts[i].match('^day')) {
						tmpDay = consts[i].substring(4);
					}
					if (consts[i].match('^view')) {
						tmpView = consts[i].substring(5);
					}
				}

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

		options['defaultDate'] = new Date(
			options['year'] + '-' +
			DPCalendar.pad(parseInt(options['month']), 2) + '-' +
			DPCalendar.pad(options['date'], 2)
		);
		options['timeZone'] = Joomla.getOptions('DPCalendar.timezone');
		options['defaultView'] = viewMapping[options['defaultView']];

		// Loading the list view when we have a small screen
		if (document.body.clientWidth < options['screen_size_list_view']) {
			options['defaultView'] = viewMapping['list'];
		}

		options['schedulerLicenseKey'] = 'GPL-My-Project-Is-Open-Source';

		options['progressiveEventRendering'] = true;
		options['weekNumberTitle'] = '';

		// Translations
		options['eventLimitText'] = Joomla.JText._('COM_DPCALENDAR_VIEW_CALENDAR_VIEW_TEXTS_MORE');
		options['allDayText'] = Joomla.JText._('COM_DPCALENDAR_VIEW_CALENDAR_ALL_DAY');
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

		options['datesRender'] = (info) => {
			// Setting the hash based on the actual view
			const d = info.view.calendar.getDate();
			const newHash = 'year=' + d.getUTCFullYear() + '&month=' + (d.getUTCMonth() + 1) + '&day=' + d.getUTCDate() + '&view=' + viewMappingReverse[info.view.type];
			if (options['use_hash'] && window.location.hash.replace(/&amp;/gi, "&").replace('#', '') != newHash) {
				window.location.hash = newHash;
			}
		};

		calendar.dpEventMarkerSet = [];
		options['datesDestroy'] = (info) => {
			const map = calendar.parentElement.querySelector('.dp-map');
			if (!DPCalendar.Map || map == null || !options['show_map']) {
				return;
			}

			calendar.dpEventMarkerSet = [];
			DPCalendar.Map.clearMarkers(map);
		};

		options['eventRender'] = (info) => {
			// Support HTML in title
			const title = info.el.querySelector('.fc-title, .fc-list-item-title');
			if (title) {
				title.innerHTML = title.textContent;
			}

			// Add a class if available
			if (info.event.view_class) {
				info.el.classList.add(info.event.view_class);
			}

			let desc = info.event.extendedProps.description;
			if (desc) {
				// Adding the hash to the url for proper return
				desc = desc.replace('task=event.delete', 'task=event.delete&urlhash=' + encodeURIComponent(window.location.hash));
				desc = desc.replace('task=event.edit', 'task=event.edit&urlhash=' + encodeURIComponent(window.location.hash));

				if (!info.isMirror) {
					const content = document.createElement('div');
					content.innerHTML = desc;
					tippy(info.el, {
						interactive: true,
						delay: 100,
						arrow: true,
						content: desc,
						ignoreAttributes: true,
						popperOptions: {
							modifiers: {
								preventOverflow: {enabled: false},
								hide: {enabled: false}
							}
						}
					});
				}
			}

			if (info.event.extendedProps.fgcolor && info.view.type != viewMapping['list']) {
				info.el.style.color = info.event.extendedProps.fgcolor;
			}

			const map = calendar.parentElement.querySelector('.dp-map');
			if (!DPCalendar.Map || map == null || !options['show_map'] || !info.event.extendedProps.location) {
				return;
			}

			// Adding the locations to the map
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

		// Handling the messages in the returned data
		options['eventDataTransform'] = (event) => {
			if (event.allDay) {
				const end = moment(event.end);
				end.add(1, 'day');
				event.end = end.format('YYYY-MM-DD');
			}
			return event;
		};

		// Drag and drop support
		options['eventDragStart'] = (info) => {
			if (info.el._tippy) {
				info.el._tippy.destroy();
			}
		};

		options['eventDrop'] = (info) => {
			if (info.event.resourceId) {
				// @Todo implement resource drop
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
				'id=' + info.event.id + '&minutes=' + moment.duration(info.delta).asMinutes() + '&allDay=' + info.event.allDay
			);
		};

		// Resize support
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
				'id=' + info.event.id + '&minutes=' + moment.duration(info.endDelta).asMinutes() + '&onlyEnd=1&allDay=' + info.event.allDay
			);
		};

		// Handling clicking on an event
		options['eventClick'] = (info) => {
			info.jsEvent.preventDefault();

			if (info.jsEvent.currentTarget._tippy) {
				info.jsEvent.currentTarget._tippy.hide();
			}

			if (options['show_event_as_popup'] == 2) {
				return;
			}

			if (options['show_event_as_popup'] == 1) {
				// Opening the Joomal modal box
				const url = new Url(info.event.url);
				url.query.tmpl = 'component';
				DPCalendar.modal(url, calendar.dataset.popupwidth, calendar.dataset.popupheight, (frame) => {
					// Check if there is a system message
					const innerDoc = frame.contentDocument || frame.contentWindow.document;
					if (!innerDoc.getElementById('system-message-container') || innerDoc.getElementById('system-message-container').children.length < 1) {
						return;
					}

					// Probably something has changed
					calendar.refetchEvents();
				});
			} else {
				// Just navigate to the event
				window.location = DPCalendar.encode(info.event.url);
			}
		};

		options['dateClick'] = (info) => {
			const form = calendar.parentElement.querySelector('.dp-quickadd .dp-form');
			const date = moment.utc(info.date);

			if (form) {
				info.jsEvent.preventDefault();

				// Setting some defaults on the quick add popup form
				if (info.view.type == viewMapping['month']) {
					date.hours(8);
					date.minutes(0);
				}

				let start = form.querySelector('#jform_start_date');
				start.value = date.format(start.getAttribute('data-format'));
				start.actualDate = start.value;
				start = form.querySelector('#jform_start_date_time');
				start.value = date.format(start.getAttribute('data-format'));
				start.actualDate = start.value;

				date.add(1, 'hours');

				let end = form.querySelector('#jform_end_date');
				end.value = date.format(end.getAttribute('data-format'));
				end.actualDate = end.value;
				end = form.querySelector('#jform_end_date_time');
				end.value = date.format(end.getAttribute('data-format'));
				end.actualDate = end.value;

				// Set location information
				if (info.resource) {
					const parts = info.resource.id.split('-');
					form.querySelector('input[name="jform[location_ids][]"]').value = [parts[0]];

					if (parts.length > 1) {
						form.querySelector('input[name="jform[rooms][]"]').value = [info.resource.id];
					}
				}

				if (options['event_create_form'] == 1 && window.innerWidth > 600) {
					new Popper(info.jsEvent.target, form.parentElement, {
						onCreate: (data) => {
							data.instance.popper.querySelector('#jform_title').focus();
						}
					});
					form.parentElement.style.display = 'block';
				} else {
					// Open the edit page
					form.querySelector('input[name=task]').value = '';
					form.submit();
				}
			} else if (options['header'].right.indexOf(viewMapping['day']) > 0) {
				// The edit form is not loaded, navigate to the day
				calendar.dpCalendar.gotoDate(info.date);
				calendar.dpCalendar.changeView(viewMapping['day']);
			}
		};

		// Custom buttons
		options['customButtons'] = {};
		if (options['header'].left.indexOf('datepicker')) {
			options['customButtons'].datepicker = {
				text: Joomla.JText._('COM_DPCALENDAR_VIEW_CALENDAR_SHOW_DATEPICKER'),
				icon: 'icon-calendar',
				click: () => {
					const button = document.querySelector('.fc-datepicker-button');
					let input = button.querySelector('input');

					if (!input) {
						input = document.createElement('input');
						input.setAttribute('type', 'hidden');
						input.id = 'datepicker-input';
						button.appendChild(input);

						input.dpPicker = new Pikaday({
							field: input,
							trigger: button,
							i18n: {
								months: names['monthNames'],
								weekdays: names['dayNames'],
								weekdaysShort: names['dayNamesShort']
							},
							onSelect: (date) => {
								const newHash = 'year=' + date.getFullYear() + '&month=' + (date.getMonth() + 1) + '&day=' + date.getDate() + '&view=' + viewMappingReverse[calendar.dpCalendar.view.type];
								if (options['use_hash'] && window.location.hash.replace(/&amp;/gi, "&").replace('#', '') != newHash) {
									window.location.hash = newHash;
								} else {
									calendar.dpCalendar.gotoDate(date);
								}
							}
						});
					}

					input.dpPicker.show();
				}
			};
		}

		if (options['header'].left.indexOf('print')) {
			options['customButtons'].print = {
				text: Joomla.JText._('COM_DPCALENDAR_VIEW_CALENDAR_TOOLBAR_PRINT'),
				icon: 'icon-print',
				click: () => {
					let loc = document.location.href.replace(/\?/, "\?layout=print&tmpl=component\&");
					if (loc == document.location.href) {
						loc = document.location.href.replace(/#/, "\?layout=print&tmpl=component#");
					}
					const printWindow = window.open(loc);
					printWindow.focus();
				}
			};
		}
		if (options['header'].left.indexOf('add') && options['event_create_url']) {
			options['customButtons'].add = {
				text: Joomla.JText._('COM_DPCALENDAR_VIEW_CALENDAR_TOOLBAR_PRINT'),
				icon: 'icon-add',
				click: () => {
					location.href = options['event_create_url'];
				}
			};
		}

		// Spinner handling
		options['loading'] = (bool) => {
			if (bool) {
				calendar.parentElement.parentElement.querySelector('.dp-loader').classList.remove('dp-loader_hidden');
			} else {
				calendar.parentElement.parentElement.querySelector('.dp-loader').classList.add('dp-loader_hidden');
			}
		};

		// Initializing local storage of event sources
		const localStorageId = calendar.getAttribute('data-options') + '-calendar-state-' + md5(options['calendarIds']);
		if (DPCalendar.isLocalStorageSupported()) {
			if (localStorage.getItem(localStorageId) == null) {
				localStorage.setItem(localStorageId, JSON.stringify(options['calendarIds']));
			} else {
				options['calendarIds'] = JSON.parse(localStorage.getItem(localStorageId)).filter((calId) => {
					return options['calendarIds'].indexOf(calId) !== -1;
				});
			}
		}

		moment.updateLocale('en', {
			months: names['monthNames'],
			monthsShort: names['monthNamesShort'],
			weekdays: names['dayNames'],
			weekdaysShort: names['dayNamesShort'],
			weekdaysMin: names['dayNamesMin']
		});

		options['plugins'] = ['dayGrid', 'timeGrid', 'list', 'interaction', 'moment'];
		const views = options['views'];
		options['views'] = {};
		Object.keys(views).forEach((view) => {
			const titleFormat = views[view]['titleFormat'];
			views[view]['titleFormat'] = (date) => {
				return moment(date.start).format(titleFormat);
			};
			options['views'][viewMapping[view]] = views[view];
		});

		let headers = '';
		options['header']['right'].split(',').forEach((view, index) => {
			headers += viewMapping[view] + ',';
		});
		options['header']['right'] = headers;

		if (headers.indexOf('resource') != -1) {
			options['plugins'].push('resourceTimeline');
			options['plugins'].push('resourceTimeGrid');
		} else if (options['resources']) {
			options['plugins'].push('resourceTimeGrid');
		}

		options['eventSources'] = [];
		options['calendarIds'].forEach((calId, index) => {
			options['eventSources'][index] = {
				id: calId,
				events: (fetchInfo, successCallback, failureCallback) => {
					DPCalendar.request(
						options['requestUrlRoot'] + '&ids=' + calId + '&date-start=' + fetchInfo.startStr + '&date-end=' + fetchInfo.endStr,
						(json) => successCallback(json.data),
						null,
						false
					);
				}
			};
		});

		const cal = new FullCalendar.Calendar(calendar, options);
		calendar.dpCalendar = cal;
		cal.render();

		// Toggle the list of calendars
		const root = calendar.parentElement;
		const toggle = root.querySelector('.com-dpcalendar-calendar__toggle');
		if (toggle) {
			toggle.addEventListener('click', () => {
				DPCalendar.slideToggle(root.querySelector('.com-dpcalendar-calendar__list'), (fadeIn) => {
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

		// Modify the calendar list
		[].slice.call(calendar.parentElement.querySelectorAll('.com-dpcalendar-calendar__list .dp-input-checkbox')).forEach((input) => {
			options['calendarIds'].forEach((calId) => {
				if (calId == input.value) {
					input.setAttribute('checked', true);
				}
			});

			input.addEventListener('click', () => {
				const calendarIds = DPCalendar.isLocalStorageSupported() ? JSON.parse(localStorage.getItem(localStorageId)) : [];

				const calId = input.value;
				if (input.checked) {
					calendar.dpCalendar.addEventSource({
						id: calId,
						events: (fetchInfo, successCallback, failureCallback) => {
							DPCalendar.request(
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

				if (DPCalendar.isLocalStorageSupported()) {
					localStorage.setItem(localStorageId, JSON.stringify(calendarIds));
				}
			});
		});
	};

}());
//# sourceMappingURL=calendar.js.map
