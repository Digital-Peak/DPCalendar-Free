document.addEventListener("DOMContentLoaded", function () {
	var elements = document.querySelectorAll('.dp-module-upcoming-modal-enabled');
	for (var i = 0; i < elements.length; i++) {
		elements[i].addEventListener('click', function (event) {
			event.preventDefault();

			var url = new Url(this.getAttribute('href'));
			url.query.tmpl = 'component';
			DPCalendar.modal(url, 0, 700);
		});
	}
});
