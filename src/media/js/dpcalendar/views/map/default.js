jQuery(document).ready(function() {
	jQuery('.dpcalendar-map-container').find('*[name="filter-location"], *[name="radius"], *[name="length_type"], *[name="ids"]').bind('change', function(e) {
		updateDPLocationFrame(this);
	});

	jQuery('.dpcalendar-map-container').each(function(index) {
		updateDPLocationFrame(this);
		jQuery(this).find('form').submit(function(e) {
			e.preventDefault();
			updateDPLocationFrame(this);
		});
	});

	jQuery(document).on('click', '.dp-event-link', function(event) {
		if (jQuery(window).width() < 600) {
			return true;
		}
		
		event.stopPropagation();

		var root = jQuery(this).closest('.dpcalendar-map-container');
		if (root.data('popup')) {
			// Opening the Joomal modal box
			var width = jQuery(window).width();
			var url = new Url(jQuery(this).attr('href'));
			url.query.tmpl = 'component';
			SqueezeBox.open(url.toString(), {
				handler : 'iframe',
				size : {
					x : (width < 650 ? width - (width * 0.10) : root.data('popupwidth')),
					y : root.data('popupheight')
				}
			});
		} else {
			window.location = dpEncode(jQuery(this).attr('href'));
		}
		return false;
	});
});

function updateDPLocationFrame(input) {
	var root = jQuery(input).closest('.dpcalendar-map-container');
	var data = {};
	data['filter-location'] = root.find('*[name="filter-location"]').val();
	data['ids'] = root.find('*[name="ids"]').val().split(',');
	data['filter-radius'] = root.find('*[name="radius"]').val();
	data['filter-length_type'] = root.find('*[name="length_type"]').val();
	data['moduleId'] = root.find('.module_id').val();
	jQuery.ajax({
		type : 'get',
		url : 'index.php?option=com_dpcalendar&view=map&layout=events&format=raw&Itemid=' + root.find('input[name="itemid"]').val(),
		data : data,
		success : function(response) {
			var json = jQuery.parseJSON(response);

			var mapElement = root.find('.dpcalendar-fixed-map');
			var map = mapElement.data('dpmap');
			if (map == null) {
				return;
			}
			clearDPCalendarMarkers(map);

			for (index in json) {
				var event = json[index];
				if (event === null || typeof event !== 'object') {
					continue;
				}

				jQuery.each(event.location, function(i, loc) {
					var locationData = JSON.parse(JSON.stringify(loc));
					locationData.title = event.title;
					locationData.color = event.color;
					locationData.description = event.description;

					createDPCalendarMarker(map, locationData);
				});
			}
		}
	});
}