(function (document, window, location) {
	'use strict';

	document.addEventListener('DOMContentLoaded', function () {
		document.querySelector('.com-dpcalendar-event__actions .dp-button-mail').addEventListener('click', function () {
			window.open(this.getAttribute('data-mailtohref'), 'win2', 'width=400,height=350,menubar=yes,resizable=yes');

			return false;
		});

		[].slice.call(document.querySelectorAll('.com-dpcalendar-event__actions [data-href]')).forEach(function (el) {
			el.addEventListener('click', function () {
				if (this.getAttribute('data-open') == 'window') {
					window.open(this.getAttribute('data-href'));
				} else {
					location.href = this.getAttribute('data-href');
				}

				return false;
			});
		});
	});
})(document, window, location);
