/**
 * @package   DPCalendar
 * @copyright Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */
(function () {
	'use strict';
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
				if (c === 13 && selected) {
					selected.click();
				}
				if ((c === 27 || c === 9) && auto) {
					DPCalendar.autocomplete.destroy(input);
				}
				if (c === 40 && selected && selected.nextElementSibling) {
					selected.classList.remove('dp-autocomplete__result_selected');
					selected.nextElementSibling.classList.add('dp-autocomplete__result_selected');
					auto.scrollTop = selected.offsetTop;
				}
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
				DPCalendar.autocomplete.destroy(input);
			});
		});
	};
	DPCalendar.autocomplete.show = (input) => {
		let root = input.parentElement.querySelector('.dp-autocomplete');
		if (!root || root.items < 1) {
			return;
		}
		const results = Array.from(root.children);
		let match = results.find((result) => result.dpItem.title == input.value);
		if (!match) {
			match = results.find((result) => result.dpItem.title.indexOf(input.value) > -1);
		}
		if (!match) {
			match = results[0];
		}
		if (match) {
			results.forEach((result) => result.classList.remove('dp-autocomplete__result_selected'));
			match.classList.add('dp-autocomplete__result_selected');
			root.scrollTop = match.offsetTop;
		}
		if (window.getComputedStyle(root).display !== 'none') {
			return;
		}
		DPCalendar.slideToggle(root, () => match ? root.scrollTop = match.offsetTop : null);
	};
	DPCalendar.autocomplete.destroy = (input) => {
		let root = input.parentElement.querySelector('.dp-autocomplete');
		if (!root) {
			return;
		}
		DPCalendar.slideToggle(root);
		root.parentElement.removeChild(root);
	};
	DPCalendar.autocomplete.setItems = (input, items) => {
		if (document.activeElement !== input) {
			return;
		}
		let root = input.parentElement.querySelector('.dp-autocomplete');
		if (root && root.items === items) {
			DPCalendar.autocomplete.show(input);
			return;
		}
		if (root) {
			root.parentElement.removeChild(root);
		}
		if (items.length === 0) {
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
				e.innerHTML = '<strong class="dp-autocomplete__item-title">' + item.title + '</strong> <span class="dp-autocomplete__item-details">' + item.details + '</span>';
				e.classList.add('dp-autocomplete__result');
				e.dpItem = item;
				e.addEventListener('click', (ev) => {
					ev.preventDefault();
					input.value = item.title;
					input.dispatchEvent(new CustomEvent('dp-autocomplete-select', { detail: item }));
					root.parentElement.removeChild(root);
					return false;
				});
				e.addEventListener('mousedown', (ev) => ev.preventDefault());
				root.appendChild(e);
			});
			Popper.createPopper(input, root, {
				placement: 'bottom-start',
				modifiers: [{
					name: 'sameWidth',
					enabled: true,
					fn: ({ state }) => {
						state.styles.popper.width = state.rects.reference.width + `px`;
					},
					effect: ({ state }) => {
						state.elements.popper.style.width = state.elements.reference.offsetWidth + 'px';
					},
					phase: 'beforeWrite',
					requires: ['computeStyles']
				}]
			});
			DPCalendar.autocomplete.show(input);
		});
	};
})();
