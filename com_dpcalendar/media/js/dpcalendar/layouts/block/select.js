/**
 * @package   DPCalendar
 * @copyright Digital Peak GmbH. <https://www.digital-peak.com>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */
(function () {
	'use strict';
	var trash = "<svg xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 448 512\"><!-- Font Awesome Free 5.15.4 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free (Icons: CC BY 4.0, Fonts: SIL OFL 1.1, Code: MIT License) --><path d=\"M432 32H312l-9.4-18.7A24 24 0 0 0 281.1 0H166.8a23.72 23.72 0 0 0-21.4 13.3L136 32H16A16 16 0 0 0 0 48v32a16 16 0 0 0 16 16h416a16 16 0 0 0 16-16V48a16 16 0 0 0-16-16zM53.2 467a48 48 0 0 0 47.9 45h245.8a48 48 0 0 0 47.9-45L416 128H32z\"/></svg>";
	class DPSelectElement {
		constructor(element, langInputPlaceholder) {
			this.element = element;
			this.multiple = element.multiple;
			this.disabled = element.disabled;
			this.classes = Array.from(this.element.classList);
			if (this.classes.length < 2) {
				this.classes.push('dp-select-container__input_unstyled');
			}
			this.langInputPlaceholder = langInputPlaceholder;
			element.addEventListener('change', () => {
				this.optionsElement.innerHTML = this.getOptionsHTML();
				this.optionsSelectedElement.innerHTML = this.getSelectedOptionsHTML();
				this.optionsListener();
				this.popperInstance.update();
			});
		}
		init() {
			Array.from(this.element.selectedOptions).forEach((option) => option.selected = true);
			this.element.classList.add('dp-select-element');
			this.container = document.createElement('div');
			this.container.classList.add('dp-select-container');
			this.container.innerHTML = `<div class="dp-select-container__options">${this.getOptionsHTML()}</div>
      <div class="dp-select-container__input ${this.classes.join(' ')}">
	  <div class="dp-select-container__options-selected">${this.getSelectedOptionsHTML()}</div>
	  <input type="text" class="dp-select-input" ${this.disabled ? 'disabled' : ''} placeholder="${this.langInputPlaceholder}" />
      </div>`;
			this.element.insertAdjacentElement('afterend', this.container);
			this.input = this.container.querySelector('.dp-select-input');
			this.optionsElement = this.container.querySelector('.dp-select-container__options');
			this.optionsSelectedElement = this.container.querySelector('.dp-select-container__options-selected');
			this.popperInstance = Popper.createPopper(this.container.querySelector('.dp-select-container__input'), this.optionsElement, {
				placement: 'bottom-start',
				modifiers: [{ name: 'offset', enabled: true }]
			});
			this.element.addEventListener('focus', () => this.input.focus());
			this.container.querySelector('.dp-select-container__input').addEventListener('click', () => this.input.focus());
			this.input.addEventListener('focus', () => {
				if (this.input.value) {
					this.input.placeholder = this.input.value;
				}
				this.input.value = '';
				this.optionsElement.classList.add('open');
				this.popperInstance.update();
			});
			this.input.addEventListener('keydown', (event) => {
				const c = event.keyCode;
				let selected = this.optionsElement.querySelector('.dp-select-option_selected');
				if (c === 13 && selected) {
					this.selectValue(selected.dataset.value);
					const next = this.find(selected, true);
					if (next) {
						next.classList.add('dp-select-option_selected');
					}
				}
				if (c === 27 || c === 9) {
					this.hideOptions();
				}
				if (c === 40) {
					if (selected !== null) {
						selected.classList.remove('dp-select-option_selected');
					}
					const next = this.find(selected, true);
					if (next) {
						next.classList.add('dp-select-option_selected');
						this.optionsElement.scrollTop = next.offsetTop - 100;
					}
				}
				if (c === 38) {
					if (selected !== null) {
						selected.classList.remove('dp-select-option_selected');
					}
					const prev = this.find(selected, false);
					if (prev) {
						prev.classList.add('dp-select-option_selected');
						this.optionsElement.scrollTop = prev.offsetTop - 30;
					}
				}
			});
			document.addEventListener('click', e => {
				if (!e.composedPath().includes(this.container)) {
					this.hideOptions();
				}
			});
			this.input.addEventListener('input', () => {
				this.updateInputWidth();
				const options = this.getOptions().map(option => {
					if ('options' in option) {
						option.options = option.options.filter(opt => {
							return opt.label.toLowerCase().trim().includes(this.input.value.toLowerCase().trim());
						});
					}
					return option;
				}).filter(option => {
					if ('options' in option) {
						return option.options.length > 0;
					}
					return option.label.toLowerCase().trim().includes(this.input.value.toLowerCase().trim());
				});
				this.optionsElement.innerHTML = this.getOptionsHTML(options);
				this.optionsListener();
			});
			if (!this.multiple) {
				const selected = this.getOptions(this.element, true).find((o) => o.value === this.element.value);
				if (selected) {
					const a = document.createElement('span');
					a.innerHTML = selected.label;
					this.input.value = a.textContent;
				}
			}
			this.updateInputWidth();
			this.optionsListener();
		}
		optionsListener() {
			Array.from(this.optionsElement.querySelectorAll('.dp-select-option:not([data-disabled="true"])')).forEach((option) => {
				option.addEventListener('click', () => this.selectValue(option.dataset.value));
			});
			Array.from(this.optionsSelectedElement.querySelectorAll('.dp-select-option:not([data-disabled="true"])')).forEach((option) => {
				option.addEventListener('click', () => {
					this.selectValue(option.dataset.value);
				});
			});
		}
		selectValue(value) {
			if (!this.multiple) {
				this.element.value = value;
				this.element.dispatchEvent(new Event('change'));
				const selected = this.getOptions(this.element, true).find((o) => o.value === value);
				if (selected) {
					const a = document.createElement('span');
					a.innerHTML = selected.label;
					this.input.value = a.textContent;
					this.updateInputWidth();
				}
				this.hideOptions();
				return;
			}
			const currentValue = Array.from(this.element.options).filter((o) => o.selected).map((o) => o.value);
			Array.from(this.element.options).forEach((option) => option.selected = false);
			const valueIndex = currentValue.indexOf(value);
			if (valueIndex !== -1) {
				currentValue.splice(valueIndex, 1);
			} else {
				currentValue.push(value);
			}
			currentValue.forEach((value) => this.element.querySelector('option[value="' + value + '"]').selected = true);
			this.element.dispatchEvent(new Event('change'));
			this.optionsElement.innerHTML = this.getOptionsHTML();
			this.optionsSelectedElement.innerHTML = this.getSelectedOptionsHTML();
			this.optionsListener();
			this.popperInstance.update();
			this.input.focus();
		}
		hideOptions() {
			this.optionsElement.innerHTML = this.getOptionsHTML();
			this.optionsListener();
			this.optionsElement.classList.remove('open');
		}
		getOptionsHTML(options = this.getOptions()) {
			let html = '';
			options.filter((o) => !o.selected).forEach((option) => {
				if ('options' in option) {
					html += `<div class="dp-select-option-group">
            <span class="dp-select-option-group__label">${option.label}</span>
            ${this.getOptionsHTML(option.options)}
          </div>`;
				} else {
					html += `<div class="dp-select-option" data-value="${option.value}" data-selected="${option.selected}" data-disabled="${option.disabled}">
            ${option.label ? option.label : ' '}
            ${option.selected && this.multiple ? '<span class="dp-select-option__icon">' + trash + '</span>' : ''}
          </div>`;
				}
			});
			return html;
		}
		getSelectedOptionsHTML(options = this.getOptions()) {
			if (!this.multiple) {
				return '';
			}
			let html = '';
			options.filter((o) => o.selected).forEach((option) => {
				if ('options' in option) {
					html += `
          <div class="dp-select-option-group">
            <span class="dp-select-option-group__label">${option.label}</span>
            ${this.getSelectedOptionsHTML(option.options)}
          </div>
        `;
				} else {
					html += `
          <div class="dp-select-option" data-value="${option.value}" data-selected="${option.selected}" data-disabled="${option.disabled}">
            ${option.label}
            ${option.selected && this.multiple ? '<span class="dp-select-option__icon">' + trash + '</span>' : ''}
          </div>`;
				}
			});
			return html;
		}
		getOptions(element = this.element, flat = false) {
			const options = [];
			Array.from(element.querySelectorAll('optgroup, option')).forEach(optionElement => {
				if (optionElement.parentElement !== element && !flat) {
					return;
				}
				if (optionElement.tagName === 'OPTION') {
					options.push({
						value: optionElement.value,
						label: optionElement.innerHTML.trim(),
						selected: this.multiple ? optionElement.selected : this.element.value === optionElement.value,
						disabled: !!optionElement.disabled
					});
				}
				if (!flat && optionElement.tagName === 'OPTGROUP') {
					options.push({
						label: optionElement.getAttribute('label'),
						options: this.getOptions(optionElement)
					});
				}
			});
			return options;
		}
		find(option, increment) {
			const options = Array.from(this.optionsElement.querySelectorAll('.dp-select-option'));
			if (option === null) {
				return options[increment ? 0 : options.length - 1];
			}
			let index = options.findIndex((o) => o.dataset.value === option.dataset.value);
			index = increment ? index + 1 : index - 1;
			if (index >= 0 && index < options.length) {
				return options[index];
			}
			return options[index < 0 ? options.length - 1 : 0];
		}
		updateInputWidth() {
			this.input.style.width = (this.input.value ? this.input.value.length : this.langInputPlaceholder.length) + 'ch';
		}
	}
	[].slice.call(document.querySelectorAll('.dp-select:not(.dp-select_plain)')).forEach((select) => {
		loadDPAssets(['/com_dpcalendar/js/popper/popper.js'], () =>
			(new DPSelectElement(select, Joomla.JText._('COM_DPCALENDAR_OPTIONS', ''))).init()
		);
	});
})();
