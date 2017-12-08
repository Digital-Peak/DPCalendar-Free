jQuery(document).ready(function () {
	if (typeof google === 'undefined') {
		return;
	}

	jQuery('#jform_street, #jform_number, #jform_zip, #jform_city, #jform_country, #jform_province').bind('change', function (e) {
		jQuery("#jform_geocomplete").val('');
		var task = 'location.loc';
		if (window.location.href.indexOf('administrator') == -1) {
			task = 'locationform.loc';
		}
		jQuery.ajax({
			url: "index.php?option=com_dpcalendar&task=" + task + "&loc=" + encodeURIComponent(getAddresString()),
			type: "POST",
			success: function (res) {
				var l = new google.maps.LatLng(0, 0);
				var json = jQuery.parseJSON(res);
				if (json.data.latitude) {
					jQuery("#jform_latitude").val(json.data.latitude);
					jQuery("#jform_longitude").val(json.data.longitude);
					var l = new google.maps.LatLng(json.data.latitude, json.data.longitude);
				} else {
					jQuery("#jform_latitude").val(0);
					jQuery("#jform_longitude").val(0);
				}
				var map = jQuery("#jform_geocomplete").geocomplete('map');
				var marker = new google.maps.Marker({
					position: l,
					map: map,
				});

				map.setCenter(l);

				if (json.messages != null && jQuery('#system-message-container').length) {
					Joomla.renderMessages(json.messages);
				}
			}
		});
	});

	jQuery("#jform_geocomplete").geocomplete({
		map: ".map_canvas",
		location: new google.maps.LatLng(jQuery("#jform_latitude").val(), jQuery("#jform_longitude").val()),
		markerOptions: {
			draggable: true
		}
	});
	jQuery("#jform_geocomplete").bind("geocode:result", function (event, result) {
		setGeoResult(result);
	});
	jQuery("#jform_geocomplete").bind("geocode:dragged", function (event, latLng) {
		jQuery.ajax({
			url: "//maps.googleapis.com/maps/api/geocode/json?latlng=" + latLng.lat() + "," + latLng.lng(),
			type: "POST",
			success: function (res) {
				if (res.results[0].address_components.length) {
					setGeoResult(res.results[0]);
				}
			}
		});
	});

	jQuery("#jform_geocomplete_find").click(function (event) {
		var task = 'location.loc';
		if (window.location.href.indexOf('administrator') == -1) {
			task = 'locationform.loc';
		}
		jQuery.ajax({
			url: "index.php?option=com_dpcalendar&task=" + task + "&loc=" + encodeURIComponent(jQuery("#jform_geocomplete").val()),
			type: "POST",
			success: function (res) {
				var json = jQuery.parseJSON(res);
				if (!json.data.latitude) {
					return;
				}
				jQuery.ajax({
					url: "//maps.googleapis.com/maps/api/geocode/json?latlng=" + json.data.latitude + "," + json.data.longitude,
					type: "POST",
					success: function (res) {
						if (res.results[0].address_components.length) {
							setGeoResult(res.results[0]);

							var l = new google.maps.LatLng(json.data.latitude, json.data.longitude);
							var map = jQuery("#jform_geocomplete").geocomplete('map');
							var marker = new google.maps.Marker({
								position: l,
								map: map,
							});

							map.setCenter(l);

							if (json.messages != null && jQuery('#system-message-container').length) {
								Joomla.renderMessages(json.messages);
							}
						}
					}
				});
			}
		});
	});
});

function getAddresString() {
	var address = '';
	var street = '';
	var city = '';
	var zip = '';
	var province = '';
	var country = '';
	if (jQuery("#jform_street").val()) {
		street = jQuery("#jform_street").val();

		if (jQuery("#jform_number").val()) {
			street += ' ' + jQuery("#jform_number").val();
		}

		street += ', ';
	}
	if (jQuery("#jform_city").val()) {
		city = jQuery("#jform_city").val();
		if (jQuery("#jform_zip").val()) {
			city += ' ' + jQuery("#jform_zip").val();
		}

		city += ', ';
	}
	if (jQuery("#jform_province").val()) {
		province = jQuery("#jform_province").val() + ', ';
	}
	if (jQuery("#jform_country").val()) {
		country = jQuery("#jform_country").val() + ', ';
	}
	return street + city + province + country;
}

function setGeoResult(result) {
	jQuery('#location-form #details input:not("#jform_title")').removeAttr('value');

	for (var i = 0; i < result.address_components.length; i++) {
		switch (result.address_components[i].types[0]) {
			case 'street_number':
				jQuery("#jform_number").val(result.address_components[i].long_name);
				break;
			case 'route':
				jQuery("#jform_street").val(result.address_components[i].long_name);
				break;
			case 'locality':
				jQuery("#jform_city").val(result.address_components[i].long_name);
				break;
			case 'administrative_area_level_1':
				jQuery("#jform_province").val(result.address_components[i].long_name);
				break;
			case 'country':
				jQuery("#jform_country").val(result.address_components[i].long_name);
				break;
			case 'postal_code':
				jQuery("#jform_zip").val(result.address_components[i].long_name);
				break;
		}
	}

	if (typeof result.geometry.location.lat === 'function') {
		jQuery("#jform_latitude").val(result.geometry.location.lat());
		jQuery("#jform_longitude").val(result.geometry.location.lng());
	} else {
		jQuery("#jform_latitude").val(result.geometry.location.lat);
		jQuery("#jform_longitude").val(result.geometry.location.lng);
	}

	if (jQuery("#jform_title").val() == '') {
		jQuery("#jform_title").val(result.formatted_address);
	}

	jQuery("#jform_geocomplete").val(result.formatted_address);
}
