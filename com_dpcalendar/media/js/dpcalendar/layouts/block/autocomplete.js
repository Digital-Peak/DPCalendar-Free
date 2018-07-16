DPCalendar = window.DPCalendar || {};

(function (document, DPCalendar) {
	'use strict';

	DPCalendar.autocomplete = {};

	DPCalendar.autocomplete.create = function (input) {
		input.addEventListener('keydown', function (event) {
			var c = event.keyCode;
			var auto = input.parentElement.querySelector('.dp-autocomplete');

			if (auto) {
				var selected = auto.querySelector('.dp-autocomplete__result_selected');
			}

			// Enter
			if (c === 13 && selected) {
				selected.click();
			}

			// ESC or tab
			if ((c === 27 || c === 9) && auto) {
				DPCalendar.slideToggle(auto);
				auto.parentElement.removeChild(auto);
			}

			// Down
			if (c === 40 && selected && selected.nextElementSibling) {
				selected.classList.remove('dp-autocomplete__result_selected');
				selected.nextElementSibling.classList.add('dp-autocomplete__result_selected');
				auto.scrollTop = selected.offsetTop;
			}

			// Up
			if (c === 38 && selected && selected.previousElementSibling) {
				selected.classList.remove('dp-autocomplete__result_selected');
				selected.previousElementSibling.classList.add('dp-autocomplete__result_selected');
				auto.scrollTop = selected.offsetTop - 30;
			}
		});

		input.addEventListener('keyup', DPCalendar.debounce(function (event) {
			var maxLength = input.getAttribute('data-max-length') ? input.getAttribute('data-max-length') : 3;
			if ([13, 27, 38, 40].includes(event.keyCode) || input.value.trim().length < maxLength) {
				return;
			}

			var e = document.createEvent('CustomEvent');
			e.initCustomEvent('dp-autocomplete-change');
			input.dispatchEvent(e);
		}));

		input.setAttribute('data-autocomplete', true);
	};

	DPCalendar.autocomplete.setItems = function (input, items) {
		var root = input.parentElement.querySelector('.dp-autocomplete');

		if (root && root.items == items) {
			return;
		}

		if (root) {
			root.parentElement.removeChild(root);
		}

		if (items.length == 0) {
			return;
		}

		root = document.createElement('div');
		root.items = items;
		root.classList.add('dp-autocomplete');
		input.parentElement.appendChild(root);

		items.forEach(function (item, index) {
			var e = document.createElement('a');
			e.href = '#';
			e.innerHTML = '<strong>' + item.title + '</strong> <span>' + item.details + '</span>';
			e.classList.add('dp-autocomplete__result');

			if (item.title == input.value) {
				e.classList.add('dp-autocomplete__result_selected');
			}

			e.addEventListener('click', function (ev) {
				ev.preventDefault();

				input.value = item.title;

				var event = document.createEvent('CustomEvent');
				event.initCustomEvent('dp-autocomplete-select', false, false, item);
				input.dispatchEvent(event);

				root.parentElement.removeChild(root);

				return false;
			});
			root.appendChild(e);
		});

		if (items && !root.querySelector('.dp-autocomplete__result_selected')) {
			root.querySelector('.dp-autocomplete__result').classList.add('dp-autocomplete__result_selected');
		}

		DPCalendar.slideToggle(root, function () {
			root.scrollTop = root.querySelector('.dp-autocomplete__result_selected').offsetTop;
		});

		new Popper(input, root, {
			placement: 'bottom-start',
			modifiers: {
				autoSizing: {
					enabled: true,
					fn: function (data) {
						data.styles.width = data.offsets.reference.width;
						return data;
					},
					order: 840
				}
			}
		});
	};

	// Close when clicked outside
	var closeListener = function (event) {
		if (!event.target || event.target.getAttribute('data-autocomplete') || event.target.closest('.dp-autocomplete')) {
			return true;
		}

		var root = document.querySelector('.dp-autocomplete');

		if (!root) {
			return true;
		}

		DPCalendar.slideToggle(root);

		if (root.parentElement) {
			root.parentElement.removeChild(root);
		}

		return true;
	};
	document.addEventListener('touchstart', closeListener);
	document.addEventListener('mousedown', closeListener);
})(document, DPCalendar);
