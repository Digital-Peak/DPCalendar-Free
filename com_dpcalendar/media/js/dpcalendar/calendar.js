(function () {
	'use strict';

	/**
	 * @package   DPCalendar
	 * @author    Digital Peak http://www.digital-peak.com
	 * @copyright Copyright (C) 2007 - 2020 Digital Peak. All rights reserved.
	 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
	 */

	function getCalendarIds(calendar, options, util)
	{
		// Default local storage when empty
		if (localStorage.getItem(options['storageId']) == null) {
			localStorage.setItem(options['storageId'], JSON.stringify(options['calendarIds']));
			return options['calendarIds'];
		}

		return JSON.parse(localStorage.getItem(options['storageId'])).filter((calId) => {
			return options['calendarIds'].indexOf(calId) !== -1;
		});
	}

	function attachStateListeners(calendar, options, util)
	{
		// Get the calendar ids from the local storage
		const calendarIds = getCalendarIds(calendar, options);

		// Toggle the list of calendars
		const root = calendar.parentElement;
		const toggle = root.querySelector('.com-dpcalendar-calendar__toggle');
		if (toggle) {
			toggle.addEventListener('click', () => {
				util.slideToggle(root.querySelector('.com-dpcalendar-calendar__list'), (fadeIn) => {
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
			calendarIds.forEach((calId) => {
				if (calId == input.value) {
					input.setAttribute('checked', true);
				}
			});

			input.addEventListener('click', () => {
				const calId = input.value;
				if (input.checked) {
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
	}

	/**
	 * @package   DPCalendar
	 * @author    Digital Peak http://www.digital-peak.com
	 * @copyright Copyright (C) 2007 - 2020 Digital Peak. All rights reserved.
	 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
	 */

	function getMappings(options)
	{
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

		// If there are resources use the proper timegrid views
		if (options['resources'] != null) {
			viewMapping['week'] = 'resourceTimeGridWeek';
			viewMapping['day'] = 'resourceTimeGridDay';
		}

		return viewMapping;
	}

	function getReversMappings(options)
	{
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
			viewMappingReverse['resourceTimeGridWeek'] = 'week';
			viewMappingReverse['resourceTimeGridDay'] = 'day';
		}

		return viewMappingReverse
	}

	/**
	 * @package   DPCalendar
	 * @author    Digital Peak http://www.digital-peak.com
	 * @copyright Copyright (C) 2007 - 2020 Digital Peak. All rights reserved.
	 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
	 */

	function setup(calendar, options)
	{
		const viewMapping = getMappings(options);

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
	}

	/**
	 * @package   DPCalendar
	 * @author    Digital Peak http://www.digital-peak.com
	 * @copyright Copyright (C) 2007 - 2020 Digital Peak. All rights reserved.
	 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
	 */

	function setup$1(calendar, options)
	{
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
	}

	/**
	 * @package   DPCalendar
	 * @author    Digital Peak http://www.digital-peak.com
	 * @copyright Copyright (C) 2007 - 2020 Digital Peak. All rights reserved.
	 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
	 */

	function setup$2(calendar, options)
	{
		const viewMapping = getMappings(options);

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
				// Opening the modal box
				DPCalendar.modal(info.event.url, calendar.dataset.popupwidth, calendar.dataset.popupheight, (frame) => {
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
					Popper.createPopper(info.jsEvent.target, form.parentElement, {
						onFirstUpdate: (state) => {
							state.elements.popper.querySelector('#jform_title').focus();
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

	/**
	 * @package   DPCalendar
	 * @author    Digital Peak http://www.digital-peak.com
	 * @copyright Copyright (C) 2007 - 2020 Digital Peak. All rights reserved.
	 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
	 */

	function setup$3(calendar, options)
	{
		// Custom buttons
		options['customButtons'] = {};
		if (options['header'].left.indexOf('datepicker')) {
			options['customButtons'].datepicker = {
				text: Joomla.JText._('COM_DPCALENDAR_VIEW_CALENDAR_SHOW_DATEPICKER'),
				icon: 'icon-calendar',
				click: () => {
					loadDPAssets(['/com_dpcalendar/js/moment/moment.js'], () => {
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
									i18n: {
										months: names['monthNames'],
										weekdays: names['dayNames'],
										weekdaysShort: names['dayNamesShort']
									},
									onSelect: (date) => {
										const newHash = 'year=' + date.getFullYear() + '&month=' + (date.getMonth() + 1) + '&day=' + date.getDate() + '&view=' + getReversMappings(options)[calendar.dpCalendar.view.type];
										if (options['use_hash'] && window.location.hash.replace(/&amp;/gi, "&").replace('#', '') != newHash) {
											window.location.hash = newHash;
										} else {
											calendar.dpCalendar.gotoDate(date);
										}
									}
								});
							}

							input.dpPicker.show();
						});
					});
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
	}

	/**
	 * @package   DPCalendar
	 * @author    Digital Peak http://www.digital-peak.com
	 * @copyright Copyright (C) 2007 - 2020 Digital Peak. All rights reserved.
	 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
	 */

	function setup$4(calendar, options)
	{
		const viewMapping = getMappings(options);
		const viewMappingReverse = getReversMappings(options);

		options['datesRender'] = (info) => {
			// Setting the hash based on the actual view
			const d = calendar.dpCalendar.getDate();
			const newHash = 'year=' + d.getUTCFullYear() + '&month=' + (d.getUTCMonth() + 1) + '&day=' + d.getUTCDate() + '&view=' + viewMappingReverse[info.view.type];
			if (options['use_hash'] && window.location.hash.replace(/&amp;/gi, "&").replace('#', '') != newHash) {
				window.location.hash = newHash;
			}
		};

		if (!options['use_hash']) {
			return;
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

	/**
	 * @package   DPCalendar
	 * @author    Digital Peak http://www.digital-peak.com
	 * @copyright Copyright (C) 2007 - 2020 Digital Peak. All rights reserved.
	 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
	 */

	function setup$5(calendar, options)
	{
		const viewMapping = getMappings(options);

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
			const title = info.el.querySelector('.fc-title, .fc-list-item-title a');
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
							popperOptions: {
								modifiers: [
									{
										name: 'preventOverflow',
										enabled: false
									},
									{
										name: 'hide',
										enabled: false
									}
								]
							}
						});
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

		// Spinner handling
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
						false
					);
				}
			};
		});
	}

	/*
	 * JavaScript MD5
	 * https://github.com/blueimp/JavaScript-MD5
	 *
	 * Copyright 2011, Sebastian Tschan
	 * https://blueimp.net
	 *
	 * Licensed under the MIT license:
	 * https://opensource.org/licenses/MIT
	 *
	 * Based on
	 * A JavaScript implementation of the RSA Data Security, Inc. MD5 Message
	 * Digest Algorithm, as defined in RFC 1321.
	 * Version 2.2 Copyright (C) Paul Johnston 1999 - 2009
	 * Other contributors: Greg Holt, Andrew Kepert, Ydnar, Lostinet
	 * Distributed under the BSD License
	 * See http://pajhome.org.uk/crypt/md5 for more info.
	 */

	/*
	 * Add integers, wrapping at 2^32. This uses 16-bit operations internally
	 * to work around bugs in some JS interpreters.
	 */
	const safeAdd = (x, y) => {
	  let lsw = (x & 0xFFFF) + (y & 0xFFFF);
	  return (((x >> 16) + (y >> 16) + (lsw >> 16)) << 16) | (lsw & 0xFFFF)
	};

	/*
	 * Bitwise rotate a 32-bit number to the left.
	 */
	const bitRotateLeft = (num, cnt) => (num << cnt) | (num >>> (32 - cnt));

	/*
	 * These functions implement the four basic operations the algorithm uses.
	 */
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
	/*
	 * Calculate the MD5 of an array of little-endian words, and a bit length.
	 */
	const binlMD5 = (x, len) => {
	  /* append padding */
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

	/*
	 * Convert an array of little-endian words to a string
	 */
	const binl2rstr = input => Array(input.length * 4).fill(8).reduce((output, k, i) => output + String.fromCharCode((input[(i * k) >> 5] >>> ((i * k) % 32)) & 0xFF), '');

	/*
	 * Convert a raw string to an array of little-endian words
	 * Characters >255 have their high-byte silently ignored.
	 */
	const rstr2binl = input => Array.from(input).map(i => i.charCodeAt(0)).reduce((output, cc, i) => {
	  let resp = output.slice();
	  resp[(i * 8) >> 5] |= (cc & 0xFF) << ((i * 8) % 32);
	  return resp
	}, []);

	/*
	 * Calculate the MD5 of a raw string
	 */
	const rstrMD5 = string => binl2rstr(binlMD5(rstr2binl(string), string.length * 8));
	/*
	 * Calculate the HMAC-MD5, of a key and some data (raw strings)
	 */
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

	/*
	 * Convert a raw string to a hex string
	 */
	const rstr2hex = input => {
	  const hexTab = (pos) => '0123456789abcdef'.charAt(pos);
	  return Array.from(input).map(c => c.charCodeAt(0)).reduce((output, x, i) => output + hexTab((x >>> 4) & 0x0F) + hexTab(x & 0x0F), '')
	};

	/*
	 * Encode a string as utf-8
	 */

	const str2rstrUTF8 = unicodeString => {
	  if (typeof unicodeString !== 'string') throw new TypeError('parameter ‘unicodeString’ is not a string');
	  const cc = c => c.charCodeAt(0);
	  return unicodeString
	    .replace(/[\u0080-\u07ff]/g,  // U+0080 - U+07FF => 2 bytes 110yyyyy, 10zzzzzz
	      c => String.fromCharCode(0xc0 | cc(c) >> 6, 0x80 | cc(c) & 0x3f))
	    .replace(/[\u0800-\uffff]/g,  // U+0800 - U+FFFF => 3 bytes 1110xxxx, 10yyyyyy, 10zzzzzz
	      c => String.fromCharCode(0xe0 | cc(c) >> 12, 0x80 | cc(c) >> 6 & 0x3F, 0x80 | cc(c) & 0x3f))
	};

	/*
	 * Take string arguments and return either raw or hex encoded strings
	 */
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

	/**
	 * @package   DPCalendar
	 * @author    Digital Peak http://www.digital-peak.com
	 * @copyright Copyright (C) 2007 - 2020 Digital Peak. All rights reserved.
	 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
	 */

	function createCalendar(calendar, options)
	{
		// Fullcalendar needs to be loaded first
		loadDPAssets(['/com_dpcalendar/js/moment/moment.js', '/com_dpcalendar/js/fullcalendar/fullcalendar.js', '/com_dpcalendar/css/fullcalendar/fullcalendar.css'], () => {
			const assets = [
				'/com_dpcalendar/js/popper/popper.js',
				'/com_dpcalendar/js/fullcalendar/fullcalendar.js',
				'/com_dpcalendar/css/fullcalendar/fullcalendar.css',
				'/com_dpcalendar/js/dpcalendar/dpcalendar.js'
			];

			if (options['resources']) {
				assets.push('/com_dpcalendar/css/scheduler/scheduler.css');
				assets.push('/com_dpcalendar/js/scheduler/scheduler.js');
			}
			loadDPAssets(assets, () => {
				calendar.parentElement.parentElement.querySelector('.dp-loader').classList.add('dp-loader_hidden');

				const viewMapping = getMappings(options);
				options['storageId'] = calendar.getAttribute('data-options') + '-calendar-state-' + md5(options['calendarIds'].toString());

				setup(calendar, options);
				setup$1(calendar, options);
				setup$2(calendar, options);
				setup$3(calendar, options);
				setup$4(calendar, options);
				setup$5(calendar, options);
				attachStateListeners(calendar, options, DPCalendar);

				const names = Joomla.getOptions('DPCalendar.calendar.names');
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
						if (view == 'list' || view == 'week') {
							return moment(date.start).format(titleFormat) + ' - ' + moment(date.end).format(titleFormat);
						}
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

				const cal = new FullCalendar.Calendar(calendar, options);
				calendar.dpCalendar = cal;
				cal.render();
			});
		});
	}

	/**
	 * @package   DPCalendar
	 * @author    Digital Peak http://www.digital-peak.com
	 * @copyright Copyright (C) 2007 - 2020 Digital Peak. All rights reserved.
	 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
	 */

	loadDPAssets(['/com_dpcalendar/js/dpcalendar/dpcalendar.js'], () => {
		[].slice.call(document.querySelectorAll('.dp-calendar')).forEach((el) => {
			if (!el.getAttribute('data-options')) {
				return;
			}

			let options = Joomla.getOptions(el.getAttribute('data-options'));
			options = Object.assign(options, el.dataset);

			if (typeof options.hiddenDays == 'string') {
				options.hiddenDays = JSON.parse(options.hiddenDays);
			}

			createCalendar(el, options);
		});
	});

}());
//# sourceMappingURL=calendar.js.map
