function createDPCalendar(calendar, options) {
	// Loading the list view when we have a small screen
	if (jQuery(document).width() < options['screen_size_list_view']) {
		options['defaultView'] = 'list';
	}

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

		if (typeof clearDPCalendarMarkers == 'function') {
			clearDPCalendarMarkers(jQuery('#dpcalendar-component-map').data('dpmap'));
		}
	};
	options['eventRender'] = function (event, element) {
		// Add a class if available
		if (event.view_class) {
			element.addClass(event.view_class);
		}

		if (event.description && typeof (element.tooltipster) == "function") {
			var desc = event.description;

			// Adding the hash to the url for proper return
			desc = desc.replace('task=event.delete', 'task=event.delete&urlhash=' + encodeURIComponent(window.location.hash));
			desc = desc.replace('task=event.edit', 'task=event.edit&urlhash=' + encodeURIComponent(window.location.hash));

			// Adding the tooltip
			element.tooltipster({
				contentAsHTML: true,
				content: dpEncode(desc),
				delay: 100,
				interactive: true
			});
			if (event.fgcolor) {
				element.css('color', event.fgcolor).find('.fc-event-inner').css('color', event.fgcolor);
			}
		}

		var map = calendar.parent().find('.dpcalendar-map').data('dpmap');
		if (map == null) {
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

			createDPCalendarMarker(map, locationData);
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
		calendar.find(calendar.attr('id') + '-loader').show();
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
				calendar.find(calendar.attr('id') + '-loader').hide();
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
		calendar.find(calendar.attr('id') + '-loader').show();
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
				calendar.find(calendar.attr('id') + '-loader').hide();
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

		// If we are on a small screen navigate to the page
		if (jQuery(window).width() < 600) {
			window.location = dpEncode(event.url);
			return false;
		}

		if (options['show_event_as_popup'] == 1) {
			// Opening the Joomal modal box
			var width = jQuery(window).width();
			var url = new Url(event.url);
			url.query.tmpl = 'component';
			SqueezeBox.open(url.toString(), {
				handler: 'iframe',
				size: {
					x: (width < 650 ? width - (width * 0.10) : calendar.data('popupwidth')),
					y: calendar.data('popupheight')
				}
			});
		} else {
			// Just navigate to the event
			window.location = dpEncode(event.url);
		}
		return false;
	};

	options['dayClick'] = function (date, jsEvent, view, resourceObj) {
		var form = calendar.parent().find('form[name=adminForm]');
		if (form.length > 0 && options['event_create_form'] == 1) {
			// On small screens open the edit page directly
			if (jQuery(window).width() < 600) {
				form.find('input[name=task]').val('');
				form.submit();
				return false;
			}
			jsEvent.stopPropagation();

			// Setting some defaults on the quick add popup form
			if (view.name == 'month') {
				date.hours(8);
			}

			form.find('#jform_start_date').datepicker('setDate', date.toDate());

			// Setting the actual date without hours to prevent shifting
			var actualDate = new Date(date);
			actualDate.setHours(0);
			form.find('#jform_start_date').data('actualDate', actualDate);
			form.find('#jform_start_date_time').timepicker('setTime', date.toDate());
			form.find('#jform_end_date').datepicker('setDate', date.toDate());
			date.hours(date.hours() + 1);
			form.find('#jform_end_date_time').timepicker('setTime', date.toDate());

			var pointerEventToXY = function (e) {
				var out = {x: 0, y: 0};
				if (e.type == 'touchstart' || e.type == 'touchmove' || e.type == 'touchend' || e.type == 'touchcancel') {
					var touch = e.originalEvent.touches[0] || e.originalEvent.changedTouches[0];
					out.x = touch.pageX;
					out.y = touch.pageY;
				} else if (e.type == 'mousedown' || e.type == 'mouseup' || e.type == 'mousemove' || e.type == 'mouseover' || e.type == 'mouseout' || e.type == 'mouseenter' || e.type == 'mouseleave') {
					out.x = e.pageX;
					out.y = e.pageY;
				}
				return out;
			};

			var height = form.parent().height();
			var width = form.parent().width();
			var top = pointerEventToXY(jsEvent).y;
			var left = pointerEventToXY(jsEvent).x - (width / 2);

			// Get the viewport width (w) and height (y)
			var w = window, d = document, e = d.documentElement, g = d.getElementsByTagName('body')[0],
				x = w.innerWidth || e.clientWidth || g.clientWidth, y = w.innerHeight || e.clientHeight || g.clientHeight;

			// Check if the popup height is outside the viewport
			if (y < (top + height - jQuery(window).scrollTop())) {
				top = top - height;
			}

			// Check if the popup left is outside the viewport
			if (left < 0) {
				left = 0;
			}
			if ((left + width) > x) {
				left = x - width;
			}

			// Show the quick add popup
			form.parent().css({
				top: top,
				left: left
			});
			form.parent().show();
			form.find('#jform_title').focus();
		}
		else if (options['event_create_form'] == 2 && typeof DPCALENDAR_CREATE_FORM_URL !== 'undefined') {
			// Just navigate to the event form
			window.location = dpEncode(DPCALENDAR_CREATE_FORM_URL);
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
				var picker = jQuery('#dp-calendar-date-picker');
				if (picker.length < 1) {
					picker = jQuery('<input type="hidden" name="date-picker" id="dp-calendar-date-picker"/>');
				}
				jQuery(this).after(picker);

				// Initialize the datepicker
				picker.datepicker({
					dateFormat: 'dd-mm-yy',
					changeYear: true,
					dayNames: options['dayNames'],
					dayNamesShort: options['dayNamesShort'],
					dayNamesMin: options['dayNamesMin'],
					monthNames: options['monthNames'],
					monthNamesShort: options['monthNamesShort'],
					firstDay: options['firstDay'],
					showButtonPanel: true,
					closeText: Joomla.JText._('JCANCEL', true),
					currentText: Joomla.JText._('COM_DPCALENDAR_VIEW_CALENDAR_TOOLBAR_TODAY', true),
					onSelect: function (dateText, inst) {
						var d = picker.datepicker('getDate');
						var view = calendar.fullCalendar('getView').name;
						calendar.fullCalendar('gotoDate', moment(d));
					}
				});
				jQuery('.ui-widget-overlay').on('click', function () {
					jQuery('#dpcalendar-dialog').dialog('close');
				});

				picker.datepicker('show');
			}
		};
	}

	if (options['header'].left.indexOf('print')) {
		options['customButtons'].print = {
			text: Joomla.JText._('COM_DPCALENDAR_VIEW_CALENDAR_TOOLBAR_PRINT'),
			icon: 'icon-print',
			click: function () {
				var loc = document.location.href.replace(/\?/, "\?layout=print&format=raw\&");
				if (loc == document.location.href)
					loc = document.location.href.replace(/#/, "\?layout=print&format=raw#");
				var printWindow = window.open(loc);
				printWindow.focus();
			}
		};
	}

	// Spinner handling
	options['loading'] = function (bool) {
		var loader = calendar.parent().find('.dpcalendar-loader');
		if (bool) {
			loader.show();
		} else {
			loader.hide();
		}
	};
	calendar.find(calendar.attr('id') + '-loader').hide();

	calendar.data('eventSources', options['eventSources']);

	// Initializing local storage of event sources
	var hash = md5(JSON.stringify(options['eventSources']));
	if (isLocalStorageNameSupported()) {
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
			success: dpCalendarEventsFetchSuccess
		});
	}
	options['eventSources'] = sources;

	// Loading the calendar
	calendar.fullCalendar(jQuery.extend({}, options));

	// Replace class names with the one from Joomla
	jQuery('.fc-icon-icon-print').attr('class', options['icon_print']);
	jQuery('.fc-icon-icon-calendar').attr('class', options['icon_calendar']);
}

function updateDPCalendarFrame(input, calendar) {
	var hash = md5(JSON.stringify(calendar.data('eventSources')));
	var eventSources = isLocalStorageNameSupported() ? JSON.parse(localStorage.getItem(calendar.attr('id') + hash)) : [];

	var source = {
		url: input.val(),
		success: dpCalendarEventsFetchSuccess
	};
	if (input.is(':checked')) {
		calendar.fullCalendar('addEventSource', source);
		eventSources.push(source.url);
	} else {
		calendar.fullCalendar('removeEventSource', source);
		jQuery.each(eventSources, function (index, value) {
			if (value == source.url) {
				eventSources.splice(index, 1);
			}
		});
	}

	if (isLocalStorageNameSupported()) {
		localStorage.setItem(calendar.attr('id') + hash, JSON.stringify(eventSources));
	}
}

function isLocalStorageNameSupported() {
	var testKey = 'test';
	try {
		localStorage.setItem(testKey, '1');
		localStorage.removeItem(testKey);
		return true;
	} catch (error) {
		return false;
	}
}

function dpCalendarEventsFetchSuccess(events) {
	// Handling the messages in the returned data
	if (events.length && events[0].messages != null && jQuery('#system-message-container').length) {
		Joomla.renderMessages(events[0].messages);
	}
	if (events.length && events[0].data != null) {
		return events[0].data;
	}
	return events;
}
