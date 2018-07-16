(function (document) {
	'use strict';

	document.addEventListener('DOMContentLoaded', function () {
		document.querySelector('.com-dpcalendar-tickets__actions .dp-input-text').addEventListener('change', function () {
			this.form.submit();

			return false;
		});
		document.querySelector('.com-dpcalendar-tickets__actions .dp-button-search').addEventListener('click', function () {
			this.form.submit();

			return false;
		});
		document.querySelector('.com-dpcalendar-tickets__actions .dp-button-clear').addEventListener('click', function () {
			this.parentElement.parentElement.querySelector('.dp-input-text').value = '';
			this.form.submit();

			return false;
		});
		document.querySelector('.com-dpcalendar-tickets__actions .dp-input-checkbox').addEventListener('change', function () {
			this.form.submit();

			return false;
		});
	});
})(document);
