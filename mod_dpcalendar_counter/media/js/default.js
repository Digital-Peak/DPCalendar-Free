jQuery(document).ready(function() {
	jQuery(document).on('click', '.dp-module-counter-link', function(event) {
		if (jQuery(window).width() < 600) {
			return true;
		}
		event.stopPropagation();
		var modal = jQuery(this).closest('.dp-module-counter-root').find('.dp-module-counter-modal');
		var width = jQuery(window).width();
		var url = new Url(jQuery(this).attr('href'));
		url.query.tmpl = 'component';
		SqueezeBox.open(url.toString(), {
			handler : 'iframe',
			size : {
				x : (width < 650 ? width - (width * 0.10) : modal.width() < 650 ? 650 : modal.width()),
				y : modal.height()
			}
		});
		return false;
	});
});