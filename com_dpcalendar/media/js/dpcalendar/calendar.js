DPCalendar = window.DPCalendar || {};

(function (DPCalendar) {
	"use strict";

	DPCalendar.createCalendar = function (calendar, options) {
		DPCalendar.loader('hide', calendar.parent()[0]);

		options['defaultDate'] = moment(
			options['year'] + '-' +
			DPCalendar.pad(parseInt(options['month']), 2) + '-' +
			DPCalendar.pad(options['date'], 2)
		);

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
				if (date.getYear() != d.year() || date.month() != d.month() || date.date() != d.date())
					calendar.fullCalendar('gotoDate', date);
				if (view.name != tmpView)
					calendar.fullCalendar('changeView', tmpView);
			});
		}

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

			var map = document.getElementById('dp-calendar-map');
			if (map && map.dpmap) {
				DPCalendar.Map.clearMarkers(map.dpmap);
			}
		};
		options['eventRender'] = function (event, element) {
			// Add a class if available
			if (event.view_class) {
				element.addClass(event.view_class);
			}

			if (event.description) {
				var desc = event.description;

				// Adding the hash to the url for proper return
				desc = desc.replace('task=event.delete', 'task=event.delete&urlhash=' + encodeURIComponent(window.location.hash));
				desc = desc.replace('task=event.edit', 'task=event.edit&urlhash=' + encodeURIComponent(window.location.hash));

				var content = document.createElement('div');
				content.innerHTML = desc;
				tippy(element[0], {interactive: true, delay: 100, arrow: true, html: content});

				if (event.fgcolor) {
					element.css('color', event.fgcolor).find('.fc-event-inner').css('color', event.fgcolor);
				}
			}

			var map = calendar[0].parentElement.querySelector('.dpcalendar-map');
			if (map == null || map.dpmap == null) {
				return;
			}

			// Adding the locations to the map
			jQuery.each(event.location, function (i, loc) {
				var locationData = JSON.parse(JSON.stringify(loc));
				locationData.title = event.title;
				locationData.color = event.color;

				var desc = event.description;
				if (event.url) {
					desc = desc.replace(event.title, '<a href="' + event.url + '"">' + event.title + "</a>");
				}
				locationData.description = desc;

				DPCalendar.Map.createMarker(map.dpmap, locationData);
			});
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
			DPCalendar.loader('show', calendar.parent()[0]);
			jQuery(jsEvent.target).tooltip('hide');
			jQuery.ajax({
				type: 'POST',
				url: 'index.php?option=com_dpcalendar&task=event.move',
				data: {
					id: event.id,
					minutes: delta.asMinutes(),
					allDay: delta.asMinutes() == 0
				},
				success: function (data) {
					DPCalendar.loader('hide', calendar.parent()[0]);
					var json = jQuery.parseJSON(data);
					if (json.data.url)
						event.url = json.data.url;
					if (json.messages != null && jQuery('#system-message-container').length) {
						Joomla.renderMessages(json.messages);
					}
				}
			});
		};

		// Resize support
		options['eventResize'] = function (event, delta, revertFunc, jsEvent, ui, view) {
			DPCalendar.loader('show', calendar.parent()[0]);
			jQuery(jsEvent.target).tooltip('hide');
			jQuery.ajax({
				type: 'POST',
				url: 'index.php?option=com_dpcalendar&task=event.move',
				data: {
					id: event.id,
					minutes: delta.asMinutes(),
					allDay: false,
					onlyEnd: true
				},
				success: function (data) {
					DPCalendar.loader('hide', calendar.parent()[0]);
					var json = jQuery.parseJSON(data);
					if (json.data.url)
						event.url = json.data.url;
					if (json.messages != null && jQuery('#system-message-container').length) {
						Joomla.renderMessages(json.messages);
					}

					if (!json.data.success) {
						revertFunc();
					}
				}
			});
		};

		// Handling clicking on an event
		options['eventClick'] = function (event, jsEvent, view) {
			jsEvent.stopPropagation();

			if (options['show_event_as_popup'] == 2) {
				return false;
			}

			if (options['show_event_as_popup'] == 1) {
				// Opening the Joomal modal box
				var url = new Url(event.url);
				url.query.tmpl = 'component';
				DPCalendar.modal(url, calendar.data('popupwidth'), calendar.data('popupheight'),function (frame) {
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

		options['dayClick'] = function (date, jsEvent, view) {
			var form = calendar.parent().find('form[name=adminForm]');

			if (form.length > 0) {
				jsEvent.stopPropagation();

				// Setting some defaults on the quick add popup form
				if (view.name == 'month') {
					date.hours(8);
				}

				var start = form.find('#jform_start_date');
				start.val(date.format(start.attr('format')));

				var end = form.find('#jform_end_date');
				end.val(date.format(end.attr('format')));

				form.find('#jform_start_date_time').timepicker('setTime', date.toDate());
				date.hours(date.hours() + 1);
				form.find('#jform_end_date_time').timepicker('setTime', date.toDate());

				if (options['event_create_form'] == 1 && jQuery(window).width() > 600) {
					new Popper(jsEvent.target, form.parent()[0], {
						onCreate: function (data) {
							data.instance.popper.querySelector('#jform_title').focus();
						}
					});
					form.parent().show();
				} else {
					// Open the edit page
					form.find('input[name=task]').val('');
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
							calendar.fullCalendar('gotoDate', picker.getMoment());
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

		// Loading the calendar
		calendar.fullCalendar(jQuery.extend({}, options));

		// Replace class names with the one from Joomla
		jQuery('.fc-icon-icon-print').attr('class', options['icon_print']);
		jQuery('.fc-icon-icon-calendar').attr('class', options['icon_calendar']);

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
		jQuery.each(options['eventSources'], function (index, value) {
			jQuery('#dp-calendar-list input').each(function (indexInput) {
				var input = jQuery(this);
				if (value.url == input.val()) {
					input.attr('checked', true);
				}
			});
		});
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
