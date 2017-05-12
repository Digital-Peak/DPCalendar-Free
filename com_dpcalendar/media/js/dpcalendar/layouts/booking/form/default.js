jQuery(document).ready(function () {
	jQuery(document).on('click', 'label[for="jform_series0"], label[for="jform_series1"]', function () {
		if (jQuery("#jform_series1").is(':checked')) {
			jQuery('#dp-bookingform-options-events-table .dp-bookingform-event-instance').hide();
			jQuery('#dp-bookingform-options-events-table .dp-bookingform-event-instance select').val('0');
		} else {
			jQuery('#dp-bookingform-options-events-table .dp-bookingform-event-instance').show();
			jQuery('#dp-bookingform-options-events-table .dp-bookingform-event-instance select').val('1');
		}
		calculatePrice();
	});
	jQuery('#dp-bookingform-options-events-table .dp-bookingform-event-instance').hide();

	jQuery('#dp-bookingform-options-events-table select').change(function () {
		calculatePrice();
	});

	calculatePrice();

	jQuery('#dp-bookingform-options-payment').hide();
});

function checkIfPaymentIsneeded(event) {
	event.preventDefault();

	if (jQuery('#dp-bookingform-options-total-price-price-content').text() > 0 && jQuery('.dp-bookingform-payment-plugin').length && !jQuery("input[name='paymentmethod']:checked").val()) {
		jQuery('#dp-bookingform-options-payment').fadeToggle('slow');
		jQuery(".dp-bookingform-payment-plugin").click(function () {
			jQuery(this).find("input[name='paymentmethod']").attr('checked', 'checked');
			Joomla.submitbutton('bookingform.save');
		});
	} else {
		Joomla.submitbutton('bookingform.save');
	}

	return false;
}

function calculatePrice() {
	var data = jQuery("#dp-bookingform").find('input[name!=task], select').serialize();
	jQuery.ajax({
		type: 'POST',
		url: PRICE_URL,
		data: data,
		success: function (response) {
			var json = jQuery.parseJSON(response);

			if (json.messages != null && jQuery('#system-message-container').length) {
				Joomla.renderMessages(json.messages);
			}

			var events = json.data.events;
			for (var id in events) {
				jQuery('#dp-bookingform-options-events-table-body-row-' + id + '-price-live').html(events[id].discount + ' ' + json.data.currency);
				if (events[id].discount != events[id].original) {
					jQuery('#dp-bookingform-options-events-table-body-row-' + id + '-price-live').show();
					jQuery('#dp-bookingform-options-events-table-body-row-' + id + '-price-original').show();
					jQuery('#dp-bookingform-options-events-table-body-row-' + id + '-price-original').html(events[id].original + ' ' + json.data.currency);
					jQuery('#dp-bookingform-options-events-table-body-row-' + id + '-price-info-icon').show();
				} else {
					jQuery('#dp-bookingform-options-events-table-body-row-' + id + '-price-original').hide();
					jQuery('#dp-bookingform-options-events-table-body-row-' + id + '-price-info-icon').hide();
				}
			}
			jQuery('#dp-bookingform-options-total-price-price-content').html(json.data.total);
			jQuery('#dp-bookingform-options-total-price-price-currency').html(json.data.currency);
		},
		complete: function (request) {
			jQuery('#dp-bookingform-loader').hide();
		}
	});
}


function dpBookingUpdateEmail() {
	var data = {};
	data['ajax'] = '1';
	data['id'] = jQuery('#jform_user_id_id').val();
	jQuery.ajax({
		type: 'POST',
		url: 'index.php?option=com_dpcalendar&task=booking.mail',
		data: data,
		success: function (data) {
			var json = jQuery.parseJSON(data);
			if (json.success) {
				jQuery('#jform_name').val(json.data.name);
				jQuery('#jform_email').val(json.data.email);
			}
		}
	});
}