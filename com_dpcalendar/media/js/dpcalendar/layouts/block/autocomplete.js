(function () {
	'use strict';

	/**
	 * @package   DPCalendar
	 * @author    Digital Peak http://www.digital-peak.com
	 * @copyright Copyright (C) 2007 - 2020 Digital Peak. All rights reserved.
	 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
	 */

	const DPCalendar = window.DPCalendar || {};
	window.DPCalendar = DPCalendar;

	DPCalendar.autocomplete = {};

	DPCalendar.autocomplete.create = (input) => {
		loadDPAssets(['/com_dpcalendar/js/dpcalendar/dpcalendar.js'], () => {
			input.addEventListener('keydown', (event) => {
				const c = event.keyCode;
				const auto = input.parentElement.querySelector('.dp-autocomplete');
				let selected = null;

				if (auto) {
					selected = auto.querySelector('.dp-autocomplete__result_selected');
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

			input.addEventListener('keyup', DPCalendar.debounce((event) => {
				const maxLength = input.getAttribute('data-max-length') ? input.getAttribute('data-max-length') : 3;
				if ([13, 27, 38, 40].includes(event.keyCode) || input.value.trim().length < maxLength) {
					return;
				}

				input.dispatchEvent(new CustomEvent('dp-autocomplete-change'));
			}));

			input.addEventListener('blur', (e) => {
				if (e.relatedTarget && e.relatedTarget.classList.contains('dp-autocomplete__result')) {
					return true;
				}

				const dropdown = e.target.parentElement.querySelector('.dp-autocomplete');
				if (!dropdown) {
					return true;
				}

				DPCalendar.slideToggle(dropdown);
				dropdown.parentElement.removeChild(dropdown);
			});
		});
	};

	DPCalendar.autocomplete.setItems = (input, items) => {
		let root = input.parentElement.querySelector('.dp-autocomplete');

		if (root && root.items == items) {
			return;
		}

		if (root) {
			root.parentElement.removeChild(root);
		}

		if (items.length == 0) {
			return;
		}

		loadDPAssets(['/com_dpcalendar/js/popper/popper.js', '/com_dpcalendar/js/dpcalendar/dpcalendar.js'], () => {
			root = document.createElement('div');
			root.items = items;
			root.classList.add('dp-autocomplete');
			input.parentElement.appendChild(root);

			items.forEach((item, index) => {
				const e = document.createElement('a');
				e.href = '#';
				e.innerHTML = '<strong>' + item.title + '</strong> <span>' + item.details + '</span>';
				e.classList.add('dp-autocomplete__result');

				if (item.title == input.value) {
					e.classList.add('dp-autocomplete__result_selected');
				}

				e.addEventListener('click', (ev) => {
					ev.preventDefault();

					input.value = item.title;

					input.dispatchEvent(new CustomEvent('dp-autocomplete-select', {detail: item}));

					root.parentElement.removeChild(root);

					return false;
				});

				// Needed to disable blur when element is selected, in Safari the blur event is called before click
				// https://stackoverflow.com/a/57630197/356375
				e.addEventListener('mousedown', (ev) => ev.preventDefault());

				root.appendChild(e);
			});

			if (items && !root.querySelector('.dp-autocomplete__result_selected')) {
				root.querySelector('.dp-autocomplete__result').classList.add('dp-autocomplete__result_selected');
			}

			DPCalendar.slideToggle(root, () => {
				root.scrollTop = root.querySelector('.dp-autocomplete__result_selected').offsetTop;
			});

			Popper.createPopper(input, root, {
				placement: 'bottom-start',
				modifiers: [{
					name: 'sameWidth',
					enabled: true,
					fn: ({state}) => {
						state.styles.popper.width = state.rects.reference.width + `px`;
					},
					phase: 'beforeWrite',
					requires: ['computeStyles']
				}]
			});
		});
	};

}());
//# sourceMappingURL=autocomplete.js.map
