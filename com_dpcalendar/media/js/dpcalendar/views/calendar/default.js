(function (document, window) {
	'use strict';

	document.addEventListener('DOMContentLoaded', function () {
		var noLink = document.querySelector('.com-dpcalendar-calendar_printable');
		if (noLink) {
			setInterval(function () {
				[].slice.call(noLink.querySelectorAll('a')).forEach(function (link) {
					link.removeAttribute('href');
				});
			}, 2000);
		}

		var quickAdd = document.querySelector('.com-dpcalendar-calendar__quickadd');

		if (quickAdd == null) {
			return;
		}

		document.onkeydown = function (evt) {
			evt = evt || window.event;
			var isEscape = false;
			if ('key' in evt) {
				isEscape = (evt.key == 'Escape' || evt.key == 'Esc');
			} else {
				isEscape = (evt.keyCode == 27);
			}
			if (isEscape) {
				quickAdd.style.display = 'none';
			}
		};

		window.addEventListener('hashchange', function () {
			quickAdd.querySelector('input[name=urlhash]').value = window.location.hash;
		});
		quickAdd.querySelector('input[name=urlhash]').value = window.location.hash;

		quickAdd.querySelector('.dp-quickadd__button-submit').addEventListener('click', function () {
			quickAdd.querySelector('input[name=task]').value = 'event.save';
			quickAdd.querySelector('.dp-form').submit();
		});
		quickAdd.querySelector('.dp-quickadd__button-edit').addEventListener('click', function () {
			quickAdd.querySelector('.dp-form').submit();
		});
		quickAdd.querySelector('.dp-quickadd__button-cancel').addEventListener('click', function () {
			quickAdd.querySelector('input[name="jform[title]"]').value = '';
			quickAdd.style.display = 'none';
		});
	});
})(document, window);
