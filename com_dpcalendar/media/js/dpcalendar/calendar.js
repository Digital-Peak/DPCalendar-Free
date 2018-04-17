DPCalendar = window.DPCalendar || {};

(function (DPCalendar) {
	"use strict";

	DPCalendar.createCalendar = function (calendar, options) {

		var adaptScroll = function (date) {
			if (!date) {
				date = calendar.fullCalendar('getDate');
			}
			var cell = calendar[0].querySelector('th[data-date^="' + date.format("YYYY-MM-DD") + '"]');
			if (!cell) {
				return;
			}

			var scroller = calendar[0].querySelector('.fc-time-area .fc-scroller');

			if (!scroller) {
				return;
			}

			scroller.scrollLeft = 0;

			var scrollerBounds = scroller.getBoundingClientRect();
			var cellBounds = cell.getBoundingClientRect();

			scroller.scrollLeft = cellBounds.left + cellBounds.width / 2 - scrollerBounds.left - scrollerBounds.width / 2;
		}

		DPCalendar.loader('hide', calendar.parent()[0]);

		if (options['use_hash']) {
			// Parsing the hash
			var vars = window.location.hash.replace(/&amp;/gi, '&').split('&');
			for (var i = 0; i < vars.length; i++) {
				if (vars[i].match('^#year'))
					options['year'] = vars[i].substring(6);
				if (vars[i].match('^month'))
					options['month'] = vars[i].substring(6);
				if (vars[i].match('^day'))
					options['date'] = vars[i].substring(4);
				if (vars[i].match('^view'))
					options['defaultView'] = vars[i].substring(5);
			}

			// Listening for hash/url changes
			window.addEventListener('hashchange', function () {
				var today = new Date();
				var tmpYear = today.getFullYear();
				var tmpMonth = today.getMonth() + 1;
				var tmpDay = today.getDate();
				var tmpView = options['defaultView'];
				var vars = window.location.hash.replace(/&amp;/gi, '&').split('&');
				for (var i = 0; i < vars.length; i++) {
					if (vars[i].match('^#year'))
						tmpYear = vars[i].substring(6);
					if (vars[i].match('^month'))
						tmpMonth = vars[i].substring(6) - 1;
					if (vars[i].match('^day'))
						tmpDay = vars[i].substring(4);
					if (vars[i].match('^view'))
						tmpView = vars[i].substring(5);
				}
				var date = new Date(tmpYear, tmpMonth, tmpDay, 0, 0, 0);
				var d = calendar.fullCalendar('getDate');
				var view = calendar.fullCalendar('getView');
				if (date.getYear() != d.year() || date.month() != d.month() || date.date() != d.date()) {
					calendar.fullCalendar('gotoDate', date);
				}
				if (view.name != tmpView) {
					calendar.fullCalendar('changeView', tmpView);
				}
			});
		}

		options['defaultDate'] = moment(
			options['year'] + '-' +
			DPCalendar.pad(parseInt(options['month']), 2) + '-' +
			DPCalendar.pad(options['date'], 2)
		);

		// Loading the list view when we have a small screen
		if (document.body.clientWidth < options['screen_size_list_view']) {
			options['defaultView'] = 'list';
		}

		options['schedulerLicenseKey'] = 'GPL-My-Project-Is-Open-Source';

		options['weekNumberTitle'] = '';
		options['theme'] = false;
		options['startParam'] = 'date-start';
		options['endParam'] = 'date-end';

		// Translations
		options['eventLimitText'] = Joomla.JText._('COM_DPCALENDAR_VIEW_CALENDAR_VIEW_TEXTS_MORE', true);
		options['allDayText'] = Joomla.JText._('COM_DPCALENDAR_VIEW_CALENDAR_ALL_DAY', true);
		options['buttonText'] = {
			today: Joomla.JText._('COM_DPCALENDAR_VIEW_CALENDAR_TOOLBAR_TODAY', true),
			month: Joomla.JText._('COM_DPCALENDAR_VIEW_CALENDAR_VIEW_MONTH', true),
			week: Joomla.JText._('COM_DPCALENDAR_VIEW_CALENDAR_VIEW_WEEK', true),
			day: Joomla.JText._('COM_DPCALENDAR_VIEW_CALENDAR_VIEW_DAY', true),
			list: Joomla.JText._('COM_DPCALENDAR_VIEW_CALENDAR_VIEW_LIST', true)
		};

		options['listTexts'] = {
			until: Joomla.JText._('COM_DPCALENDAR_VIEW_CALENDAR_VIEW_TEXTS_UNTIL', true),
			past: Joomla.JText._('COM_DPCALENDAR_VIEW_CALENDAR_VIEW_TEXTS_PAST', true),
			today: Joomla.JText._('COM_DPCALENDAR_VIEW_CALENDAR_VIEW_TEXTS_TODAY', true),
			tomorrow: Joomla.JText._('COM_DPCALENDAR_VIEW_CALENDAR_VIEW_TEXTS_TOMORROW', true),
			thisWeek: Joomla.JText._('COM_DPCALENDAR_VIEW_CALENDAR_VIEW_TEXTS_THIS_WEEK', true),
			nextWeek: Joomla.JText._('COM_DPCALENDAR_VIEW_CALENDAR_VIEW_TEXTS_NEXT_WEEK', true),
			thisMonth: Joomla.JText._('COM_DPCALENDAR_VIEW_CALENDAR_VIEW_TEXTS_THIS_MONTH', true),
			nextMonth: Joomla.JText._('COM_DPCALENDAR_VIEW_CALENDAR_VIEW_TEXTS_NEXT_MONTH', true),
			future: Joomla.JText._('COM_DPCALENDAR_VIEW_CALENDAR_VIEW_TEXTS_FUTURE', true),
			week: Joomla.JText._('COM_DPCALENDAR_VIEW_CALENDAR_VIEW_TEXTS_WEEK', true)
		};

		options['viewRender'] = function (view) {
			// Setting the hash based on the actual view
			var d = calendar.fullCalendar('getDate');
			var newHash = 'year=' + d.year() + '&month=' + (d.month() + 1) + '&day=' + d.date() + '&view=' + view.name;
			if (options['use_hash'] && window.location.hash.replace(/&amp;/gi, "&") != newHash) {
				window.location.hash = newHash;
			}

			adaptScroll();

			var map = calendar[0].parentElement.querySelector('.dpcalendar-map');
			if (map == null || map.dpmap == null || !options['show_map']) {
				return;
			}
			DPCalendar.Map.clearMarkers(map.dpmap);
		};

		options['eventRender'] = function (event, e, view) {
			var element = e[0];
			// Add a class if available
			if (event.view_class) {
				element.classList.add(event.view_class);
			}

			if (event.description) {
				var desc = event.description;

				// Adding the hash to the url for proper return
				desc = desc.replace('task=event.delete', 'task=event.delete&urlhash=' + encodeURIComponent(window.location.hash));
				desc = desc.replace('task=event.edit', 'task=event.edit&urlhash=' + encodeURIComponent(window.location.hash));

				var content = document.createElement('div');
				content.innerHTML = desc;
				element.tippy = tippy(element, {
					interactive: true,
					delay: 100,
					arrow: true,
					html: content,
					flipDuration: 0,
					performance: true,
					popperOptions: {
						modifiers: {
							preventOverflow: {
								enabled: false
							}
						}
					}
				});
			}

			if (event.fgcolor && view.name != 'list') {
				element.style.color = event.fgcolor;
			}

			var map = calendar[0].parentElement.querySelector('.dpcalendar-map');
			if (map == null || map.dpmap == null || !options['show_map']) {
				return;
			}

			// Adding the locations to the map
			for (var i = 0; i < event.location.length; i++) {
				var locationData = JSON.parse(JSON.stringify(event.location[i]));
				locationData.title = event.title;
				locationData.color = event.color;

				var desc = event.description;
				if (event.url) {
					desc = desc.replace(event.title, '<a href="' + event.url + '"">' + event.title + "</a>");
				}
				locationData.description = desc;

				DPCalendar.Map.createMarker(map.dpmap, locationData);
			}
		};

		// Handling the messages in the returned data
		options['eventDataTransform'] = function (event) {
			if (event.allDay) {
				var end = moment(event.end);
				end.add(1, 'day');
				event.end = end;
			}
			return event;
		};

		// Drag and drop support
		options['eventDrop'] = function (event, delta, revertFunc, jsEvent, ui, view) {
			if (event.resourceId) {
				// @Todo implement resource drop
				revertFunc();
				return false;
			}

			DPCalendar.loader('show', calendar.parent()[0]);
			Joomla.request({
				method: 'POST',
				url: 'index.php?option=com_dpcalendar&task=event.move',
				data: 'id=' + event.id + '&minutes=' + delta.asMinutes() + '&allDay=' + (delta.asMinutes() == 0),
				onSuccess: function (data) {
					DPCalendar.loader('hide', calendar.parent()[0]);

					var json = JSON.parse(data);

					if (json.data.url) {
						event.url = json.data.url;
					}

					if (!json.success) {
						revertFunc();
						return;
					}

					if (json.messages == null) {
						revertFunc();
						return;
					}

					if (document.getElementById('system-message-container')) {
						Joomla.renderMessages(json.messages);
					}

					for (var type in json.messages) {
						if (type != 'message') {
							revertFunc();
							return;
						}
					}
				}
			});
		};

		// Resize support
		options['eventResize'] = function (event, delta, revertFunc, jsEvent, ui, view) {
			DPCalendar.loader('show', calendar.parent()[0]);
			Joomla.request({
				method: 'POST',
				url: 'index.php?option=com_dpcalendar&task=event.move',
				data: 'id=' + event.id + '&minutes=' + delta.asMinutes() + '&onlyEnd=1&allDay=' + (delta.asMinutes() == 0),
				onSuccess: function (data) {
					DPCalendar.loader('hide', calendar.parent()[0]);

					var json = JSON.parse(data);

					if (json.data.url) {
						event.url = json.data.url;
					}

					if (!json.success) {
						revertFunc();
						return;
					}

					if (json.messages == null) {
						revertFunc();
						return;
					}

					if (document.getElementById('system-message-container')) {
						Joomla.renderMessages(json.messages);
					}

					for (var type in json.messages) {
						if (type != 'message') {
							revertFunc();
							return;
						}
					}
				}
			});
		};

		// Handling clicking on an event
		options['eventClick'] = function (event, jsEvent, view) {
			jsEvent.stopPropagation();

			var tippy = jsEvent.currentTarget.tippy;
			if (tippy) {
				tippy.hide(tippy.getPopperElement(jsEvent.currentTarget));
			}

			if (options['show_event_as_popup'] == 2) {
				return false;
			}

			if (options['show_event_as_popup'] == 1) {
				// Opening the Joomal modal box
				var url = new Url(event.url);
				url.query.tmpl = 'component';
				DPCalendar.modal(url, calendar.data('popupwidth'), calendar.data('popupheight'), function (frame) {
					// Check if there is a system message
					var innerDoc = frame.contentDocument || frame.contentWindow.document;
					if (innerDoc.getElementById('system-message').children.length < 1) {
						return;
					}

					// Probably something has changed
					calendar.fullCalendar('refetchEvents');
				});
			} else {
				// Just navigate to the event
				window.location = DPCalendar.encode(event.url);
			}
			return false;
		};

		options['dayClick'] = function (date, jsEvent, view, resource) {
			var form = calendar[0].parentElement.querySelector('form[name=adminForm]');

			if (form) {
				jsEvent.stopPropagation();

				// Setting some defaults on the quick add popup form
				if (view.name == 'month') {
					date.hours(8);
				}

				var start = form.querySelector('#jform_start_date');
				start.value = date.format(start.getAttribute('format'));

				var end = form.querySelector('#jform_end_date');
				end.value = date.format(end.getAttribute('format'));

				jQuery(form.querySelector('#jform_start_date_time')).timepicker('setTime', new Date(date.toISOString()));
				date.hours(date.hours() + 1);
				jQuery(form.querySelector('#jform_end_date_time')).timepicker('setTime', new Date(date.toISOString()));

				// Set location information
				if (resource) {
					var parts = resource.id.split('-');
					form.querySelector('input[name="jform[location_ids][]"]').value = [parts[0]];

					if (parts.length > 1) {
						form.querySelector('input[name="jform[rooms][]"]').value = [resource.id];
					}
				}

				if (options['event_create_form'] == 1 && window.innerWidth > 600) {
					new Popper(jsEvent.target, form.parentElement, {
						onCreate: function (data) {
							data.instance.popper.querySelector('#jform_title').focus();
						}
					});
					form.parentElement.style.display = 'block';
				} else {
					// Open the edit page
					form.querySelector('input[name=task]').value = '';
					form.submit();
				}
			} else if (options['header'].right.indexOf('agendaDay') > 0) {
				// The edit form is not loaded, navigate to the day
				calendar.fullCalendar('gotoDate', date);
				calendar.fullCalendar('changeView', 'agendaDay');
			}
		};

		// Custom buttons
		options['customButtons'] = {};
		if (options['header'].left.indexOf('datepicker')) {
			options['customButtons'].datepicker = {
				text: Joomla.JText._('COM_DPCALENDAR_VIEW_CALENDAR_SHOW_DATEPICKER'),
				icon: 'icon-calendar',
				click: function () {
					var button = document.querySelector('.fc-datepicker-button');
					var input = document.getElementById(calendar.attr('id') + '-datepicker-input');

					if (!input) {
						input = document.createElement('input');
						input.setAttribute('type', 'hidden');
						input.id = calendar.attr('id') + '-datepicker-input';
						button.appendChild(input);
					}

					var picker = new Pikaday({
						field: input,
						trigger: button,
						i18n: {
							months: options['monthNames'],
							weekdays: options['dayNames'],
							weekdaysShort: options['dayNamesShort']
						},
						onSelect: function (date) {
							var d = picker.getMoment();
							var newHash = 'year=' + d.year() + '&month=' + (d.month() + 1) + '&day=' + d.date() + '&view=' + calendar.fullCalendar('getView').name;
							if (options['use_hash'] && window.location.hash.replace(/&amp;/gi, "&") != newHash) {
								window.location.hash = newHash;
							} else {
								calendar.fullCalendar('gotoDate', d);
							}
							adaptScroll(d);
							this.destroy();
						}
					});
					picker.show();
				}
			};
		}

		if (options['header'].left.indexOf('print')) {
			options['customButtons'].print = {
				text: Joomla.JText._('COM_DPCALENDAR_VIEW_CALENDAR_TOOLBAR_PRINT'),
				icon: 'icon-print',
				click: function () {
					var loc = document.location.href.replace(/\?/, "\?layout=print&tmpl=component\&");
					if (loc == document.location.href)
						loc = document.location.href.replace(/#/, "\?layout=print&tmpl=component#");
					var printWindow = window.open(loc);
					printWindow.focus();
				}
			};
		}

		// Spinner handling
		options['loading'] = function (bool) {
			DPCalendar.loader(bool ? 'show' : 'hide', calendar.parent()[0]);
		};

		calendar.data('eventSources', options['eventSources']);

		// Initializing local storage of event sources
		var hash = md5(JSON.stringify(options['eventSources']));
		if (DPCalendar.isLocalStorageSupported()) {
			if (localStorage.getItem(calendar.attr('id') + hash) == null) {
				localStorage.setItem(calendar.attr('id') + hash, JSON.stringify(options['eventSources']));
			} else {
				options['eventSources'] = JSON.parse(localStorage.getItem(calendar.attr('id') + hash));
			}
		}

		// Convert to more intelligent resources
		var sources = [];
		for (var i = 0; i < options['eventSources'].length; i++) {
			sources.push({
				url: options['eventSources'][i],
				success: DPCalendar.extractEvents
			});
		}
		options['eventSources'] = sources;

		moment.updateLocale('en', {
			months: options['dayNames'],
			monthsShort: options['monthNamesShort'],
			weekdays: options['dayNames'],
			weekdaysShort: options['dayNamesShort'],
			weekdaysMin: options['dayNamesMin']
		});

		// Loading the calendar
		calendar.fullCalendar(options);

		adaptScroll();

		// Replace class names with the one from Joomla
		var icon = document.querySelector('.fc-icon-icon-print');
		if (icon) {
			icon.setAttribute('class', options['icon_print']);
		}

		icon = document.querySelector('.fc-icon-icon-calendar');
		if (icon) {
			icon.setAttribute('class', options['icon_calendar']);
		}

		// Toggle the list of calendars
		var root = calendar[0].parentElement;
		var toggle = root.querySelector('.dp-calendar-toggle');
		if (toggle) {
			toggle.addEventListener('click', function (event) {
				DPCalendar.slideToggle(root.querySelector('.dp-calendar-list'), function (fadeIn) {
					if (!fadeIn) {
						root.querySelector('i[data-direction="up"]').style.display = 'none';
						root.querySelector('i[data-direction="down"]').style.display = 'inline';
					} else {
						root.querySelector('i[data-direction="up"]').style.display = 'inline';
						root.querySelector('i[data-direction="down"]').style.display = 'none';
					}
				});
			});
		}

		// Modify the calendar list
		for (var i = 0; i < options['eventSources'].length; i++) {
			var elements = calendar[0].parentElement.querySelectorAll('.dp-calendar-list input');
			for (var j = 0; j < elements.length; j++) {
				if (options['eventSources'][i].url == elements[j].value) {
					elements[j].setAttribute('checked', true);
				}
			}
		}
	}

	DPCalendar.updateCalendar = function (input, calendar) {
		var hash = md5(JSON.stringify(calendar.data('eventSources')));
		var eventSources = DPCalendar.isLocalStorageSupported() ? JSON.parse(localStorage.getItem(calendar.attr('id') + hash)) : [];

		var source = {
			url: input.val(),
			success: DPCalendar.extractEvents
		};
		if (input.is(':checked')) {
			calendar.fullCalendar('addEventSource', source);
			eventSources.push(source.url);
		} else {
			calendar.fullCalendar('removeEventSource', source);

			for (var i = 0; i < eventSources.length; i++) {
				if (eventSources[i] == source.url) {
					eventSources.splice(i, 1);
				}
			}
		}

		if (DPCalendar.isLocalStorageSupported()) {
			localStorage.setItem(calendar.attr('id') + hash, JSON.stringify(eventSources));
		}
	}

	DPCalendar.extractEvents = function (events) {
		// Handling the messages in the returned data
		if (events.length && events[0].messages.message != null && document.getElementById('system-message-container')) {
			Joomla.renderMessages(events[0].messages);
		}

		if (events.length && events[0].data != null) {
			return events[0].data;
		}
		return events;
	}
}(DPCalendar));
