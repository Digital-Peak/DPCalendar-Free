jQuery(document).ready(function () {
	jQuery('.field-scheduling, #jform_scheduling_monthly_options, #jform_scheduling_daily_weekdays').bind('click', function (e) {
		changeVisiblity();
		updateRuleFromForm();
	});
	jQuery('#jform_scheduling_end_date, #jform_scheduling_interval, #jform_scheduling_repeat_count').bind('change', function () {
		updateRuleFromForm();
	});
	jQuery('#jform_scheduling_weekly_days, #jform_scheduling_monthly_days, #jform_scheduling_monthly_week_days, #jform_scheduling_monthly_week').bind('change', function () {
		updateRuleFromForm();
	});
	jQuery('#jform_rrule').bind('change', function (e) {
		updateFormFromRule();
	});
	updateFormFromRule();

	jQuery('#scheduling-expert-button').click(function () {
		jQuery('#scheduling-rrule').children().fadeToggle();
	});
	jQuery('#scheduling-rrule').children().hide();

	jQuery('#jform_all_day input').bind('click', function (e) {
		var input = jQuery(this);
		if (input.val() == 0) {
			jQuery('#jform_start_date_time, #jform_end_date_time').show();
		} else {
			jQuery('#jform_start_date_time, #jform_end_date_time').hide();
		}

		checkOverlapping();
	});

	if (jQuery('#jform_all_day0')[0].checked || (!jQuery('#jform_all_day0')[0].checked && !jQuery('#jform_all_day1')[0].checked)) {
		jQuery('#jform_start_date_time, #jform_end_date_time').show();
	} else {
		jQuery('#jform_start_date_time, #jform_end_date_time').hide();
	}

	jQuery('#jform_start_date, #jform_start_date_time, #jform_end_date, #jform_end_date_time').change(function (e) {
		setTimeout(checkOverlapping, 2000);
	});

	// Because of com_fields, we need to call it with a delay
	jQuery('#jform_catid').bind('change', function (e) {
		setTimeout(checkOverlapping, 2000);
		return true;
	});

	jQuery('#dp-event-form-message-box').hide();
	setTimeout(checkOverlapping, 2000);
	setTimeout(updateLocationFrame, 2000);

	// Add the timepair class
	jQuery('#dp-event-form-container-tabs-tab-content').addClass('timepair');

	if (Joomla.JText._('COM_DPCALENDAR_ONLY_AVAILABLE_SUBSCRIBERS')) {
		var selector = '.field-scheduling .controls, #dp-event-form-container-tabs-tab-booking .controls';
		jQuery(selector).append('<span class="dp-event-form-free-information-text">' + Joomla.JText._('COM_DPCALENDAR_ONLY_AVAILABLE_SUBSCRIBERS') + '</span>');
	}

	jQuery('#jform_location_ids').bind('change', function (e) {
		Joomla.loadingLayer('show');
		jQuery('input[name=task]').val('event.reload');
		this.form.submit();
	});

	jQuery('#dp-event-form-map-form-save').click(function () {
		var data = {
			jform: {
				title: jQuery('#location_title').val(),
				country: jQuery('#location_country').val(),
				province: jQuery('#location_province').val(),
				city: jQuery('#location_city').val(),
				zip: jQuery('#location_zip').val(),
				street: jQuery('#location_street').val(),
				number: jQuery('#location_number').val(),
				state: 1,
				language: '*'
			}
		};
		data[jQuery('#dp-event-form-map-form-token').val()] = '1';
		data['ajax'] = '1';
		data['id'] = 0;
		jQuery.ajax({
			type: 'POST',
			url: 'index.php?option=com_dpcalendar&task=locationform.save',
			data: data,
			success: function (data) {
				var json = jQuery.parseJSON(data);
				if (json.data.id != null && json.data.display != null) {
					jQuery('#jform_location_ids').append('<option value="' + json.data.id + '" selected="selected">' + json.data.display + '</option>');
					jQuery('#jform_location_ids').trigger("liszt:updated");
					updateLocationFrame();

					jQuery('#dp-event-form-map-form-container input').val('');
				}
				Joomla.renderMessages(json.messages);
			}
		});
	});

	var form = jQuery('#dp-event-form-map-form');
	form.hide();
	jQuery('#dp-event-form-map-title-toggle-up').hide();

	var root = jQuery('#dp-event-form-map-title-toggle');
	root.bind('click', function (e) {
		form.slideToggle('slow', function () {
			if (!form.is(':visible')) {
				root.find('i[data-direction="up"]').hide();
				root.find('i[data-direction="down"]').show();
			} else {
				root.find('i[data-direction="up"]').show();
				root.find('i[data-direction="down"]').hide();
			}
		});
	});
});

function checkOverlapping() {
	if (jQuery('#dp-event-form-message-box').length < 1) {
		return;
	}
	jQuery('#dp-event-form-message-box').hide();

	// Choosen doesn't update the selected value
	var data = jQuery("#dp-event-form").find('input[name!=task], select');
	var url = new Url();
	jQuery.ajax({
		type: 'post',
		url: 'index.php?option=com_dpcalendar&task=event.overlapping',
		data: data.serialize() + '&id=' + url.query.e_id,
		success: function (data) {
			var json = jQuery.parseJSON(data);
			if (json.messages != null && jQuery('#system-message-container').length) {
				Joomla.renderMessages(json.messages);
			}

			if (json.data.message) {
				var box = jQuery('#dp-event-form-message-box');
				box.show();
				box.attr('class', 'alert ' + (json.data.count ? '' : 'alert-success'));
				box.html(json.data.message);

				jQuery('.save-button').attr("disabled", json.data.count > 0);
			}
		}
	});
}

function updateFormFromRule() {
	if (jQuery('#jform_rrule').val() == undefined) {
		return;
	}
	var frequency = null;
	jQuery.each(jQuery('#jform_rrule').val().split(';'), function () {
		var parts = this.split('=');
		if (parts.length > 1) {
			switch (parts[0]) {
				case 'FREQ':
					jQuery('#jform_scheduling input').each(function () {
						var sched = jQuery(this);
						if (parts[1] == sched.val()) {
							sched.attr('checked', 'checked');
							if (parts[1] == '0') {
							} else {
								frequency = sched.val();
							}
						}

						if (parts[1] == 'DAILY') {
							jQuery('#jform_scheduling_daily_weekdays0').attr('checked', null);
							jQuery('#jform_scheduling_daily_weekdays1').attr('checked', 'checked');
						}
					});
					break;
				case 'BYDAY':
					// Daily without weekend
					if (frequency == 'WEEKLY' && parts[1] == 'MO,TU,WE,TH,FR') {
						jQuery('#jform_scheduling_daily_weekdays0').attr('checked', 'checked');
						jQuery('#jform_scheduling_daily_weekdays1').attr('checked', null);
						jQuery('#jform_scheduling1').attr('checked', 'Checked');
					}

					jQuery.each(parts[1].split(','), function () {
						if (frequency == 'MONTHLY') {
							var pos = this.length;
							var day = this.substring(pos - 2, pos);
							var week = this.substring(0, pos - 2);

							if (week == -1) {
								week = 'last';
							}

							jQuery('#jform_scheduling_monthly_week option[value="' + week + '"]').prop('selected', true);
							jQuery('#jform_scheduling_monthly_week_days option[value="' + day + '"]').prop('selected', true);
						} else {
							jQuery('#jform_scheduling_weekly_days option[value="' + this + '"]').prop('selected', true);
						}
					});
					break;
				case 'BYMONTHDAY':
					jQuery('#jform_scheduling_monthly_options input[value="by_day"]').attr('checked', 'checked');
					jQuery.each(parts[1].split(','), function () {
						jQuery('#jform_scheduling_monthly_days option[value="' + this + '"]').prop('selected', true);
					});
					break;
				case 'COUNT':
					jQuery('#jform_scheduling_repeat_count').val(parts[1]);
					break;
				case 'INTERVAL':
					jQuery('#jform_scheduling_interval').val(parts[1]);
					break;
				case 'UNTIL':
					var untilField = document.getElementById('jform_scheduling_end_date');
					untilField.value = moment.utc(parts[1]).format(untilField.getAttribute('format'));
					break;
			}
		}
	});
	changeVisiblity();
}

function updateRuleFromForm() {
	var rule = '';
	if (jQuery('#jform_scheduling1')[0].checked) {
		rule = 'FREQ=DAILY';
		if (jQuery('#jform_scheduling_daily_weekdays0')[0].checked) {
			rule = 'FREQ=WEEKLY;BYDAY=MO,TU,WE,TH,FR';
		}
	}
	if (jQuery('#jform_scheduling2')[0].checked) {
		rule = 'FREQ=WEEKLY';

		var boxes = jQuery('#jform_scheduling_weekly_days option:selected');
		if (boxes.length > 0) {
			rule += ';BYDAY=';
			boxes.each(function () {
				rule += jQuery(this).val() + ',';
			});
			rule = rule.slice(0, -1);
		}
	}
	if (jQuery('#jform_scheduling3')[0].checked) {
		rule = 'FREQ=MONTHLY';
		if (jQuery('#jform_scheduling_monthly_options0')[0].checked) {
			var boxes = jQuery('#jform_scheduling_monthly_days option:selected');
			if (boxes.length > 0) {
				rule += ';BYMONTHDAY=';
				boxes.each(function () {
					rule += jQuery(this).val() + ',';
				});
				rule = rule.slice(0, -1);
			}
		} else {
			var weeks = jQuery('#jform_scheduling_monthly_week option:selected');
			var trim = false;
			if (weeks.length > 0) {
				rule += ';BYDAY=';
				weeks.each(function () {
					var days = jQuery('#jform_scheduling_monthly_week_days option:selected');
					if (days.length > 0) {
						var week = jQuery(this).val();
						if (week == 'last') {
							week = -1;
						}
						days.each(function () {
							rule += week + jQuery(this).val() + ',';
							trim = true;
						});
					}
				});
				if (trim)
					rule = rule.slice(0, -1);
			}
		}
	}
	if (jQuery('#jform_scheduling4')[0].checked) {
		rule = 'FREQ=YEARLY';
	}
	if (rule.length > 1) {
		var interval = jQuery('#jform_scheduling_interval').val();
		if (interval > 0) {
			rule += ';INTERVAL=' + interval;
		}
		var count = jQuery('#jform_scheduling_repeat_count').val();
		if (count > 0) {
			rule += ';COUNT=' + count;
		}

		var untilField = document.getElementById('jform_scheduling_end_date');
		var until = moment(untilField.value, untilField.getAttribute('format'));
		if (until.isValid()) {
			rule += ';UNTIL=' + until.format('YYYYMMDD') + 'T235900Z';
		}
	}
	jQuery('#jform_rrule').val(rule);
}

function updateLocationFrame() {
	if (jQuery('#jform_rooms').length < 1) {
		jQuery('#dp-event-form-map').hide();
		return;
	}

	// Move map to right position
	jQuery('#dp-event-form-map').detach().appendTo(jQuery('#jform_rooms').parent().parent().parent());

	var map = document.getElementById('dp-event-form-map-frame');
	if (map == null || map.dpmap == null) {
		return;
	}
	DPCalendar.Map.clearMarkers(map.dpmap);
	jQuery('#jform_location_ids option:selected').each(function () {
		var content = jQuery(this).html();
		var parts = content.substring(content.lastIndexOf('[') + 1, content.lastIndexOf(']')).split(':');
		if (parts.length < 2)
			return;
		if (parts[0] == 0 && parts[1] == 0)
			return;

		DPCalendar.Map.createMarker(map.dpmap, {latitude: parts[0], longitude: parts[1], title: content});
	});
}

function changeVisiblity() {
	// no scheduling
	if (jQuery('#jform_scheduling0')[0].checked) {
		jQuery('.field-scheduling_end_date').parent().hide();
		jQuery('.field-scheduling_interval').parent().hide();
		jQuery('.field-scheduling_repeat_count').parent().hide();
		jQuery('.field-rrule').parent().hide();
	} else {
		jQuery('.field-scheduling_end_date').parent().show();
		jQuery('.field-scheduling_interval').parent().show();
		jQuery('.field-scheduling_repeat_count').parent().show();
		jQuery('.field-rrule').parent().show();
	}

	// daily
	if (jQuery('#jform_scheduling1')[0].checked) {
		jQuery('.field-scheduling_daily_weekdays').parent().show();
	} else {
		jQuery('.field-scheduling_daily_weekdays').parent().hide();
	}

	// weekly
	if (jQuery('#jform_scheduling2')[0].checked) {
		jQuery('.field-scheduling_weekly_days').parent().show();
	} else {
		jQuery('.field-scheduling_weekly_days').parent().hide();
	}

	// monthly
	if (jQuery('#jform_scheduling3')[0].checked) {
		jQuery('.field-scheduling_monthly_options').parent().show();
		jQuery('.field-scheduling_monthly_week').parent().show();
		jQuery('.field-scheduling_monthly_week_days').parent().show();
		jQuery('.field-scheduling_monthly_days').parent().show();

		if (jQuery('#jform_scheduling_monthly_options0')[0].checked) {
			jQuery('.field-scheduling_monthly_week').parent().hide();
			jQuery('.field-scheduling_monthly_week_days').parent().hide();
			jQuery('.field-scheduling_monthly_days').parent().show();
		} else {
			jQuery('.field-scheduling_monthly_week').parent().show();
			jQuery('.field-scheduling_monthly_week_days').parent().show();
			jQuery('.field-scheduling_monthly_days').parent().hide();
		}
	} else {
		jQuery('.field-scheduling_monthly_options').parent().hide();
		jQuery('.field-scheduling_monthly_week').parent().hide();
		jQuery('.field-scheduling_monthly_week_days').parent().hide();
		jQuery('.field-scheduling_monthly_days').parent().hide();
	}
}
