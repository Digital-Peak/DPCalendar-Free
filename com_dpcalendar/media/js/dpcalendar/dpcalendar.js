DPCalendar = window.DPCalendar || {};

// Polyfill for matches and closest
if (!Element.prototype.matches) {
	Element.prototype.matches = Element.prototype.msMatchesSelector || Element.prototype.webkitMatchesSelector
}

if (!Element.prototype.closest) {
	Element.prototype.closest = function (selector) {
		var el = this;
		if (!document.documentElement.contains(el)) {
			return null;
		}
		do {
			if (el.matches(s)) {
				return el;
			}
			el = el.parentElement;
		} while (el !== null);
		return null;
	};
}

(function (DPCalendar) {
	"use strict";

	DPCalendar.modal = function (url, width, height, closeFunction) {
		var modal = new tingle.modal({
			footer: false,
			stickyFooter: false,
			closeMethods: ['overlay', 'button', 'escape'],
			cssClass: ['dpcalendar-modal'],
			closeLabel: Joomla.JText._('COM_DPCALENDAR_CLOSE', 'Close'),
			onClose: function () {
				if (closeFunction) {
					closeFunction(modal.modalBox.children[0].querySelector('iframe'));
				}
			}
		});

		// Overwrite the width of the modal
		if (width && document.body.clientWidth > width) {
			if (!isNaN(width)) {
				width = width + 'px';
			}
			document.querySelector('.tingle-modal-box').style.width = width;
		}

		if (!isNaN(height)) {
			height = height + 'px';
		}

		modal.setContent('<iframe width="100%" height="' + height + '" src="' + url.toString() + '" frameborder="0" allowfullscreen></iframe>');
		modal.open();
	},

		DPCalendar.slideToggle = function (el, fn) {
			if (!el) {
				return;
			}

			if (!el.getAttribute('data-max-height')) {
				// Backup the styles
				var style = window.getComputedStyle(el),
					display = style.display,
					position = style.position,
					visibility = style.visibility;

				// Some defaults
				var elHeight = el.offsetHeight;

				// If its not hidden we just use normal height
				if (display === 'none') {
					// The element is hidden:
					// Making the el block so we can measure its height but still be hidden
					el.style.position = 'absolute';
					el.style.visibility = 'hidden';
					el.style.display = 'block';

					elHeight = el.offsetHeight;

					// Reverting to the original values
					el.style.display = display;
					el.style.position = position;
					el.style.visibility = visibility;
				}

				// Setting the required styles
				el.style['transition'] = 'max-height 0.5s ease-in-out';
				el.style.overflowY = 'hidden';
				el.style.maxHeight = display === 'none' ? '0px' : elHeight + 'px';
				el.style.display = 'block';

				// Backup the element height attribute
				el.setAttribute('data-max-height', elHeight + 'px');
			}

			// Flag if we fade in
			var fadeIn = el.style.maxHeight.replace('px', '').replace('%', '') === '0';

			// If a callback exists add a listener
			if (fn) {
				el.addEventListener('transitionend', function () {
					fn(fadeIn);
				}, {once: true})
			}

			// We use setTimeout to modify maxHeight later than display to have a transition effect
			setTimeout(function () {
				el.style.maxHeight = fadeIn ? el.getAttribute('data-max-height') : '0';
			}, 1);
		},

		DPCalendar.encode = function (str) {
			return str.replace(/&amp;/g, '&');
		},

		DPCalendar.pad = function (num, size) {
			var s = num + "";
			while (s.length < size) s = "0" + s;
			return s;
		},

		DPCalendar.isLocalStorageSupported = function () {
			var testKey = 'test';
			try {
				localStorage.setItem(testKey, '1');
				localStorage.removeItem(testKey);
				return true;
			} catch (error) {
				return false;
			}
		},

		DPCalendar.formToString = function (form, selector) {
			var elements = selector ? form.querySelectorAll(selector) : form.elements;
			var field, s = [];
			for (var i = 0; i < elements.length; i++) {
				field = elements[i];
				if (!field.name || field.disabled || field.type == 'file' || field.type == 'reset' || field.type == 'submit' || field.type == 'button') {
					continue;
				}

				if (field.type == 'select-multiple') {
					for (var j = elements[i].options.length - 1; j >= 0; j--) {
						if (field.options[j].selected)
							s[s.length] = encodeURIComponent(field.name) + "=" + encodeURIComponent(field.options[j].value);
					}
				} else if ((field.type != 'checkbox' && field.type != 'radio') || field.checked) {
					s[s.length] = encodeURIComponent(field.name) + "=" + encodeURIComponent(field.value);
				}
			}
			return s.join('&').replace(/%20/g, '+');
		}
}(DPCalendar));
